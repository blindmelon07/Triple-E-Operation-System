<?php

namespace App\Models;

use App\Enums\PayPeriodType;
use App\Enums\PayrollStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'payroll_number',
        'pay_period_start',
        'pay_period_end',
        'pay_period_type',
        'status',
        'generated_by',
        'approved_by',
        'approved_at',
        'paid_at',
        'total_gross',
        'total_deductions',
        'total_net',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pay_period_start' => 'date',
            'pay_period_end' => 'date',
            'pay_period_type' => PayPeriodType::class,
            'status' => PayrollStatus::class,
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'total_gross' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<PayrollItem, $this>
     */
    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Generate a unique payroll number.
     */
    public static function generatePayrollNumber(): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $lastPayroll = static::whereDate('created_at', today())->latest()->first();
        $sequence = $lastPayroll ? ((int) substr($lastPayroll->payroll_number ?? '0000', -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Check if payroll can be approved.
     */
    public function canBeApproved(): bool
    {
        return $this->status === PayrollStatus::Draft;
    }

    /**
     * Check if payroll can be marked as paid.
     */
    public function canBePaid(): bool
    {
        return $this->status === PayrollStatus::Approved;
    }

    /**
     * Check if payroll can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [PayrollStatus::Draft, PayrollStatus::Approved]);
    }

    /**
     * Recalculate totals from payroll items.
     */
    public function recalculateTotals(): void
    {
        $this->update([
            'total_gross' => $this->payrollItems()->sum('gross_pay'),
            'total_deductions' => $this->payrollItems()->sum('total_deductions'),
            'total_net' => $this->payrollItems()->sum('net_pay'),
        ]);
    }
}
