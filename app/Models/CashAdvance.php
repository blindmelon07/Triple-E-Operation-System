<?php

namespace App\Models;

use App\Enums\PayPeriodType;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAdvance extends Model
{
    /** @use HasFactory<\Database\Factories\CashAdvanceFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'employee_id',
        'user_id',
        'reference_number',
        'type',
        'date_granted',
        'amount',
        'deduction_per_payroll',
        'purpose',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_granted' => 'date',
            'amount' => 'decimal:2',
            'deduction_per_payroll' => 'decimal:2',
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
     * @return HasMany<CashAdvancePayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CashAdvancePayment::class);
    }

    public function totalPaid(): float
    {
        return (float) $this->payments()->effective()->sum('amount');
    }

    public function balance(): float
    {
        return round((float) $this->amount - $this->totalPaid(), 2);
    }

    /**
     * Advances with a remaining balance for one employee, oldest first —
     * the order payroll deducts them in.
     *
     * @return Collection<int, CashAdvance>
     */
    public static function outstandingFor(int $employeeId): Collection
    {
        return static::query()
            ->where('employee_id', $employeeId)
            ->withSum(['payments as paid_amount' => fn (Builder $q) => $q->effective()], 'amount')
            ->orderBy('date_granted')
            ->orderBy('id')
            ->get()
            ->filter(fn (CashAdvance $ca) => round((float) $ca->amount - (float) $ca->paid_amount, 2) > 0)
            ->values();
    }

    public const TYPE_REGULAR = 'regular';

    public const TYPE_EMERGENCY = 'emergency';

    /** Emergency advances must be fully repaid within this many months. */
    public const EMERGENCY_MONTHS = 2;

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_REGULAR => 'Regular (deducted in full next payroll)',
            self::TYPE_EMERGENCY => 'Emergency (up to '.self::EMERGENCY_MONTHS.' months)',
        ];
    }

    /**
     * Gross pay for one cut-off, from the employee's compensation: daily
     * rate × working days in the period. Null when no compensation is set,
     * in which case the CA limits can't be enforced.
     */
    public static function salaryPerCutoff(?int $employeeId): ?float
    {
        $comp = $employeeId ? EmployeeCompensation::where('employee_id', $employeeId)->first() : null;

        if (! $comp) {
            return null;
        }

        $rate = (float) $comp->daily_rate;
        $workDaysPerWeek = 7 - count($comp->days_off ?? []);

        return round(match ($comp->pay_period) {
            PayPeriodType::Daily => $rate,
            PayPeriodType::Weekly => $rate * $workDaysPerWeek,
            // Same 26-day month EmployeeCompensation::getMonthlyEquivalent() uses.
            PayPeriodType::SemiMonthly => $rate * 13,
        }, 2);
    }

    /**
     * How many payroll cut-offs the advance is spread over: 1 for regular,
     * EMERGENCY_MONTHS worth of the employee's pay periods for emergency
     * (8 weekly, 4 semi-monthly).
     */
    public static function cutoffsFor(string $type, ?int $employeeId): int
    {
        if ($type !== self::TYPE_EMERGENCY) {
            return 1;
        }

        $period = $employeeId
            ? EmployeeCompensation::where('employee_id', $employeeId)->first()?->pay_period
            : null;

        return self::EMERGENCY_MONTHS * match ($period) {
            PayPeriodType::SemiMonthly => 2,
            PayPeriodType::Daily => 26,
            default => 4, // weekly
        };
    }

    /**
     * Equal per-cut-off deduction, rounded up to the centavo so the advance
     * is cleared within the allowed cut-offs (payroll caps the last one at
     * the remaining balance).
     */
    public static function installmentFor(float $amount, string $type, ?int $employeeId): float
    {
        $cutoffs = self::cutoffsFor($type, $employeeId);

        return ceil(round($amount / $cutoffs * 100, 4)) / 100;
    }

    /**
     * Largest advance allowed: one cut-off's salary per installment.
     */
    public static function maxAmountFor(string $type, ?int $employeeId): ?float
    {
        $salary = self::salaryPerCutoff($employeeId);

        return $salary === null ? null : round($salary * self::cutoffsFor($type, $employeeId), 2);
    }

    /**
     * Generate a unique reference number.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'CA';
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->latest('id')->first();
        $sequence = $last ? ((int) substr($last->reference_number ?? '0000', -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
