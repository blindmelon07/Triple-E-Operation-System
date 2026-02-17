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

    public function getColor(): string
    {
        return match ($this) {
            self::Daily => 'gray',
            self::Weekly => 'info',
            self::SemiMonthly => 'success',
        };
    }
}
