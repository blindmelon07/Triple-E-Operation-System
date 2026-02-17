<?php

namespace App\Models;

use App\Enums\PayPeriodType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCompensation extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'daily_rate',
        'pay_period',
        'overtime_rate_multiplier',
        'late_deduction_type',
        'late_deduction_amount',
        'allowance',
        'allowance_description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'overtime_rate_multiplier' => 'decimal:2',
            'late_deduction_amount' => 'decimal:2',
            'allowance' => 'decimal:2',
            'pay_period' => PayPeriodType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the monthly equivalent salary (daily_rate × 26 working days).
     */
    public function getMonthlyEquivalent(): float
    {
        return (float) $this->daily_rate * 26;
    }
}
