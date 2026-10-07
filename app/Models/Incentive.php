<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A sales incentive for an employee, entered by hand. Paid out either by
 * being added to the employee's next payroll (PAYOUT_PAYROLL) or released
 * on its own (PAYOUT_SEPARATE).
 */
class Incentive extends Model
{
    use Auditable;

    public const PAYOUT_PAYROLL = 'payroll';

    public const PAYOUT_SEPARATE = 'separate';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_IN_PAYROLL = 'In Payroll';

    public const STATUS_PAID = 'Paid';

    protected $fillable = [
        'reference_number',
        'employee_id',
        'user_id',
        'date',
        'amount',
        'description',
        'payout_method',
        'payroll_item_id',
        'released_at',
        'released_by',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    /**
     * @return BelongsTo<PayrollItem, $this>
     */
    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    /**
     * @return array<string, string>
     */
    public static function payoutOptions(): array
    {
        return [
            self::PAYOUT_PAYROLL => 'Add to payroll',
            self::PAYOUT_SEPARATE => 'Release separately',
        ];
    }

    /**
     * Payroll-method incentives not yet in a live payroll: never picked up,
     * or picked up by a payroll that was later cancelled (a cancelled
     * payroll never paid anyone, so the incentive is still owed).
     *
     * @param  Builder<Incentive>  $query
     */
    public function scopeAwaitingPayroll(Builder $query): void
    {
        $query->where('payout_method', self::PAYOUT_PAYROLL)
            ->where(function (Builder $q) {
                $q->whereNull('payroll_item_id')
                    ->orWhereHas('payrollItem.payroll', fn (Builder $p) => $p->where('status', PayrollStatus::Cancelled));
            });
    }

    public function status(): string
    {
        if ($this->payout_method === self::PAYOUT_SEPARATE) {
            return $this->released_at ? self::STATUS_PAID : self::STATUS_PENDING;
        }

        $payrollStatus = $this->payrollItem?->payroll?->status;

        return match ($payrollStatus) {
            null, PayrollStatus::Cancelled => self::STATUS_PENDING,
            PayrollStatus::Paid => self::STATUS_PAID,
            default => self::STATUS_IN_PAYROLL,
        };
    }

    /**
     * Locked once it's in a live payroll or released — changing it then
     * would make the payslip or the release record wrong.
     */
    public function isLocked(): bool
    {
        return $this->status() !== self::STATUS_PENDING;
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = 'INC';
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->latest('id')->first();
        $sequence = $last ? ((int) substr($last->reference_number ?? '0000', -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
