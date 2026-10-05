<?php

namespace App\Models;

use App\Enums\PayrollStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single repayment against a cash advance — either a payroll deduction
 * (payroll_item_id set, created by payroll generation) or a manual cash
 * repayment (payroll_item_id null).
 */
class CashAdvancePayment extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'cash_advance_id',
        'payroll_item_id',
        'user_id',
        'payment_date',
        'amount',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<CashAdvance, $this>
     */
    public function cashAdvance(): BelongsTo
    {
        return $this->belongsTo(CashAdvance::class);
    }

    /**
     * @return BelongsTo<PayrollItem, $this>
     */
    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Payments that actually reduce the balance: manual repayments, plus
     * payroll deductions whose payroll wasn't cancelled. A cancelled
     * payroll never paid anyone, so its deductions must not count.
     *
     * @param  Builder<CashAdvancePayment>  $query
     */
    public function scopeEffective(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereNull('payroll_item_id')
                ->orWhereHas('payrollItem.payroll', fn (Builder $p) => $p->where('status', '!=', PayrollStatus::Cancelled));
        });
    }

    public function isPayrollDeduction(): bool
    {
        return $this->payroll_item_id !== null;
    }
}
