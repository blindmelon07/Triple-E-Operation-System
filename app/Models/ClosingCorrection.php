<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A request to change the closing amount of an already-closed register
 * session. Nothing on the session changes until a manager approves it; the
 * original amount is kept here so the change stays auditable.
 */
class ClosingCorrection extends Model
{
    protected $fillable = [
        'cash_register_session_id',
        'requested_by_id',
        'original_closing_amount',
        'corrected_amount',
        'reason',
        'status',
        'reviewed_by_id',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'original_closing_amount' => 'decimal:2',
            'corrected_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function cashRegisterSession(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class);
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

    /**
     * Locks the row so two managers reviewing at once can't both apply it.
     */
    private function ensureStillPending(): void
    {
        $fresh = static::whereKey($this->getKey())->lockForUpdate()->first();

        if ($fresh->status !== 'pending') {
            throw new RuntimeException('This correction request is no longer pending.');
        }
    }

    public function approve(User $manager): void
    {
        DB::transaction(function () use ($manager) {
            $this->ensureStillPending();

            $this->update([
                'status' => 'approved',
                'reviewed_by_id' => $manager->id,
                'reviewed_at' => now(),
            ]);

            $session = $this->cashRegisterSession;
            $old = [
                'closing_amount' => $session->closing_amount,
                'discrepancy' => $session->discrepancy,
            ];

            $session->applyClosingCorrection((float) $this->corrected_amount);

            AuditLog::create([
                'user_id' => $manager->id,
                'user_name' => $manager->name,
                'action' => 'approved_closing_correction',
                'auditable_type' => CashRegisterSession::class,
                'auditable_id' => $session->id,
                'auditable_label' => "Register Session #{$session->id}",
                'old_values' => $old,
                'new_values' => [
                    'closing_amount' => $session->closing_amount,
                    'discrepancy' => $session->discrepancy,
                    'closing_correction_id' => $this->id,
                    'requested_by' => $this->requestedBy?->name,
                    'reason' => $this->reason,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    public function reject(User $manager, ?string $reason): void
    {
        DB::transaction(function () use ($manager, $reason) {
            $this->ensureStillPending();

            $this->update([
                'status' => 'rejected',
                'reviewed_by_id' => $manager->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);
        });

        AuditLog::create([
            'user_id' => $manager->id,
            'user_name' => $manager->name,
            'action' => 'rejected_closing_correction',
            'auditable_type' => CashRegisterSession::class,
            'auditable_id' => $this->cash_register_session_id,
            'auditable_label' => "Register Session #{$this->cash_register_session_id}",
            'new_values' => [
                'closing_correction_id' => $this->id,
                'corrected_amount' => $this->corrected_amount,
                'rejection_reason' => $reason,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
