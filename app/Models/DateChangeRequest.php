<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A request to change the date on a record (e.g. a maintenance expense that
 * was entered late). Non-admins can't edit the date directly; they file one
 * of these and an admin approves or rejects it. Every step is written to the
 * audit log, and approving also triggers the record's own 'updated' audit.
 */
class DateChangeRequest extends Model
{
    /** Roles allowed to edit dates directly and to approve/reject requests. */
    public const APPROVER_ROLES = ['super_admin', 'admin'];

    protected $fillable = [
        'record_type',
        'record_id',
        'field',
        'old_date',
        'new_date',
        'reason',
        'status',
        'requested_by_id',
        'reviewed_by_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'old_date'    => 'date',
            'new_date'    => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public static function canApprove(?User $user): bool
    {
        return $user?->hasAnyRole(self::APPROVER_ROLES) ?? false;
    }

    /**
     * File a pending request to change $field on $record to $newDate.
     */
    public static function submit(Model $record, string $field, string $newDate, string $reason): self
    {
        $request = static::create([
            'record_type'     => $record->getMorphClass(),
            'record_id'       => $record->getKey(),
            'field'           => $field,
            'old_date'        => $record->getAttribute($field),
            'new_date'        => $newDate,
            'reason'          => $reason,
            'status'          => 'pending',
            'requested_by_id' => auth()->id(),
        ]);

        $request->audit('requested_date_change');

        return $request;
    }

    public function approve(): void
    {
        DB::transaction(function () {
            // Lock so two admins clicking approve at once can't both apply it.
            $fresh = static::whereKey($this->getKey())->lockForUpdate()->first();

            if ($fresh->status !== 'pending') {
                throw new RuntimeException('This request is no longer pending.');
            }

            $record = $this->record;

            if (! $record) {
                throw new RuntimeException('The record for this request no longer exists.');
            }

            // Keep the date the record actually had at approval time, in case
            // it changed since the request was filed.
            $this->old_date = $record->getAttribute($this->field);

            $record->update([$this->field => $this->new_date->toDateString()]);

            $this->update([
                'old_date'       => $this->old_date,
                'status'         => 'approved',
                'reviewed_by_id' => auth()->id(),
                'reviewed_at'    => now(),
            ]);

            $this->audit('approved_date_change');
        });
    }

    public function reject(?string $reason): void
    {
        DB::transaction(function () use ($reason) {
            $fresh = static::whereKey($this->getKey())->lockForUpdate()->first();

            if ($fresh->status !== 'pending') {
                throw new RuntimeException('This request is no longer pending.');
            }

            $this->update([
                'status'           => 'rejected',
                'reviewed_by_id'   => auth()->id(),
                'reviewed_at'      => now(),
                'rejection_reason' => $reason,
            ]);

            $this->audit('rejected_date_change');
        });
    }

    /**
     * Human-readable name of the record being changed, e.g.
     * "MaintenanceRecord MNT-20260915-0003 (SI/DR# 11517)".
     */
    public function getRecordLabelAttribute(): string
    {
        $record = $this->record;

        if (! $record) {
            return class_basename($this->record_type)." #{$this->record_id} (deleted)";
        }

        $label = class_basename($record).' '.($record->getAttribute('reference_number') ?? "#{$record->getKey()}");

        if ($si = $record->getAttribute('si_number')) {
            $label .= " (SI/DR# {$si})";
        }

        return $label;
    }

    private function audit(string $action): void
    {
        AuditLog::create([
            'user_id'         => auth()->id(),
            'user_name'       => auth()->user()?->name,
            'action'          => $action,
            'auditable_type'  => $this->record_type,
            'auditable_id'    => $this->record_id,
            'auditable_label' => "Date Change Request #{$this->id} for {$this->record_label}",
            'old_values'      => [$this->field => $this->old_date?->toDateString()],
            'new_values'      => array_filter([
                $this->field       => $this->new_date->toDateString(),
                'reason'           => $this->reason,
                'requested_by'     => $this->requestedBy?->name,
                'status'           => $this->status,
                'rejection_reason' => $this->rejection_reason,
            ], fn ($v) => $v !== null),
            'ip_address'      => request()->ip(),
            'user_agent'      => request()->userAgent(),
        ]);
    }

    public function record(): MorphTo
    {
        return $this->morphTo();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
