<?php

namespace App\Enums;

/**
 * How many device punches an employee is expected to make per day, and
 * therefore how ZkAttendanceService should fold those punches into an
 * Attendance row's time_in/time_out/total_hours.
 */
enum AttendanceLogMode: string
{
    /**
     * Check-in + check-out only. First and last punch of the day become
     * time_in/time_out directly; no break time is subtracted.
     */
    case Two = 'two';

    /**
     * Check-in, break-out, break-in, check-out. The break interval in the
     * middle is subtracted from total_hours instead of counted as worked.
     */
    case Four = 'four';

    public function getLabel(): string
    {
        return match ($this) {
            self::Two => '2 logs/day (check-in & check-out only)',
            self::Four => '4 logs/day (check-in, break-out, break-in, check-out)',
        };
    }
}
