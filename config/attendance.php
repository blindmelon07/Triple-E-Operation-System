<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shift Start Time
    |--------------------------------------------------------------------------
    |
    | Used by the ZKTeco biometric integration to decide whether a first punch
    | of the day counts as "present" or "late". 24-hour HH:MM format.
    |
    */
    'shift_start' => env('ATTENDANCE_SHIFT_START', '09:00'),

    /*
    |--------------------------------------------------------------------------
    | Late Grace Period (minutes)
    |--------------------------------------------------------------------------
    |
    | Punches within this many minutes after shift_start are still "present".
    |
    */
    'late_grace_minutes' => env('ATTENDANCE_LATE_GRACE_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Half Day Threshold (hours)
    |--------------------------------------------------------------------------
    |
    | If total worked hours for the day fall below this, status is marked
    | half_day instead of present/late (unless already absent/on_leave).
    |
    */
    'half_day_max_hours' => env('ATTENDANCE_HALF_DAY_MAX_HOURS', 4),

];
