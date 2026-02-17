<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItem extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'payroll_id',
        'user_id',
        'daily_rate',
        'days_worked',
        'days_absent',
        'overtime_hours',
        'overtime_pay',
        'bonus',
        'bonus_description',
        'allowance',
        'gross_pay',
        'late_count',
        'late_minutes',
        'late_deduction',
        'sss_deduction',
        'philhealth_deduction',
        'pagibig_deduction',
        'other_deduction',
        'other_deduction_description',
        'total_deductions',
        'net_pay',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'days_worked' => 'decimal:2',
            'days_absent' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_pay' => 'decimal:2',
            'bonus' => 'decimal:2',
            'allowance' => 'decimal:2',
            'gross_pay' => 'decimal:2',
            'late_deduction' => 'decimal:2',
            'sss_deduction' => 'decimal:2',
            'philhealth_deduction' => 'decimal:2',
            'pagibig_deduction' => 'decimal:2',
            'other_deduction' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Payroll, $this>
     */
    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
