<?php

namespace App\Enums;

enum PayPeriodType: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case SemiMonthly = 'semi_monthly';

    public function getLabel(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::SemiMonthly => 'Semi-Monthly (15th & 30th)',
        };
    }

    /**
     * Longest period (in calendar days) one payroll of this type may cover.
     * Government deductions are split per cut-off on this basis, so a longer
     * range would under-deduct.
     */
    public function maxDays(): int
    {
        return match ($this) {
            self::Daily => 1,
            self::Weekly => 7,
            self::SemiMonthly => 16,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Daily => 'gray',
            self::Weekly => 'info',
            self::SemiMonthly => 'success',
        };
    }
}
