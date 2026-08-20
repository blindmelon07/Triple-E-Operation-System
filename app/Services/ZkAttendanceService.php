<?php

namespace App\Services;

use App\Enums\AttendanceLogMode;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\ZkAttendanceLog;
use App\Models\ZkDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Turns raw ZKTeco device punches (ATTLOG rows) into Attendance records.
 *
 * A device sends one row per punch (check-in, check-out, break, etc). This
 * service stores every raw punch for audit purposes, then folds the day's
 * punches for that employee into a single Attendance row.
 */
class ZkAttendanceService
{
    /**
     * Standard ZKTeco ATTLOG status codes (column 3). Devices vary slightly,
     * but this is the near-universal convention.
     */
    protected const STATUS_CHECK_IN = 0;

    protected const STATUS_CHECK_OUT = 1;

    protected const STATUS_BREAK_OUT = 2;

    protected const STATUS_BREAK_IN = 3;

    /**
     * Remarks value used to mark an Attendance row as device-sourced, so we
     * can tell it apart from one an admin created/edited by hand and never
     * silently overwrite the latter.
     */
    protected const BIOMETRIC_REMARK = 'Recorded via ZKTeco biometric device';

    /**
     * Process one ATTLOG line, e.g.:
     * "3\t2026-08-18 08:59:03\t0\t1\t0\t0\t0"
     * columns: PIN, DateTime, Status, Verify, WorkCode(optional)...
     */
    public function processLine(ZkDevice $device, string $line): ?ZkAttendanceLog
    {
        // Tab-separated; the datetime column itself contains a space
        // ("Y-m-d H:i:s"), so splitting on generic whitespace would break it.
        $columns = array_map('trim', explode("\t", trim($line)));

        if (count($columns) < 2 || $columns[0] === '') {
            return null;
        }

        $pin = $columns[0];
        $punchedAt = $this->parseTimestamp($columns[1]);

        if (! $punchedAt) {
            Log::warning('ZKTeco: unparsable punch timestamp', ['line' => $line, 'device' => $device->serial_number]);

            return null;
        }

        $status = isset($columns[2]) && is_numeric($columns[2]) ? (int) $columns[2] : null;
        $verify = isset($columns[3]) && is_numeric($columns[3]) ? (int) $columns[3] : null;

        $employee = Employee::where('biometric_pin', $pin)->first();

        $log = ZkAttendanceLog::firstOrCreate(
            [
                'zk_device_id' => $device->id,
                'pin' => $pin,
                'punched_at' => $punchedAt,
            ],
            [
                'employee_id' => $employee?->id,
                'status' => $status,
                'verify_type' => $verify,
                'raw_line' => $line,
            ]
        );

        if ($employee) {
            $attendance = $this->foldIntoAttendance($employee, $punchedAt);
            if ($attendance && $log->attendance_id !== $attendance->id) {
                $log->update(['attendance_id' => $attendance->id]);
            }
        } else {
            Log::warning('ZKTeco: punch from unmapped PIN', ['pin' => $pin, 'device' => $device->serial_number]);
        }

        return $log;
    }

    /**
     * Link any previously-unmapped punches (logged with employee_id=null
     * because no Employee had this PIN at the time) to $employee, then
     * re-fold every day those punches touch into Attendance. Call this
     * after an Employee's biometric_pin is set/changed, so history recorded
     * before the mapping existed isn't permanently lost.
     */
    public function reconcileUnmappedPunches(Employee $employee): void
    {
        if (! $employee->biometric_pin) {
            return;
        }

        $orphaned = ZkAttendanceLog::whereNull('employee_id')
            ->where('pin', $employee->biometric_pin)
            ->get();

        if ($orphaned->isEmpty()) {
            return;
        }

        ZkAttendanceLog::whereNull('employee_id')
            ->where('pin', $employee->biometric_pin)
            ->update(['employee_id' => $employee->id]);

        $dates = $orphaned->pluck('punched_at')->map(fn (Carbon $t) => $t->toDateString())->unique();

        foreach ($dates as $date) {
            $attendance = $this->foldIntoAttendance($employee, Carbon::parse($date.' 12:00:00'));

            if ($attendance) {
                ZkAttendanceLog::where('employee_id', $employee->id)
                    ->whereDate('punched_at', $date)
                    ->update(['attendance_id' => $attendance->id]);
            }
        }

        Log::info('ZKTeco: reconciled unmapped punches after biometric_pin set', [
            'employee_id' => $employee->id,
            'pin' => $employee->biometric_pin,
            'punch_count' => $orphaned->count(),
            'days_affected' => $dates->count(),
        ]);
    }

    /**
     * Recompute the day's Attendance row for an employee from all of that
     * day's raw logs. How time_in/time_out/total_hours are derived depends
     * on the employee's AttendanceLogMode:
     *  - Two:  first punch of the day = time_in, last = time_out. No break
     *          is subtracted (the employee isn't expected to punch for one).
     *  - Four: earliest check-in punch = time_in, latest check-out punch =
     *          time_out, and any break-out/break-in pair in between is
     *          excluded from total_hours instead of counted as worked time.
     *
     * Never touches an Attendance row an admin created/edited by hand (i.e.
     * one that doesn't carry our own BIOMETRIC_REMARK marker) — a stray or
     * retransmitted punch must not clobber a manual correction like on_leave.
     */
    protected function foldIntoAttendance(Employee $employee, Carbon $punchedAt): ?Attendance
    {
        $date = $punchedAt->toDateString();

        $existing = Attendance::where('employee_id', $employee->id)->where('date', $date)->first();

        if ($existing && $existing->remarks !== self::BIOMETRIC_REMARK) {
            Log::info('ZKTeco: not overwriting manually-managed Attendance record', [
                'employee_id' => $employee->id,
                'date' => $date,
                'attendance_id' => $existing->id,
            ]);

            return $existing;
        }

        $dayLogs = ZkAttendanceLog::where('employee_id', $employee->id)
            ->whereDate('punched_at', $date)
            ->orderBy('punched_at')
            ->get();

        if ($dayLogs->isEmpty()) {
            return null;
        }

        $logMode = $employee->attendance_log_mode ?? AttendanceLogMode::Two;

        if ($logMode === AttendanceLogMode::Four) {
            $checkIn = $dayLogs->firstWhere('status', self::STATUS_CHECK_IN);
            $checkOut = $dayLogs->where('status', self::STATUS_CHECK_OUT)->last();

            $timeIn = ($checkIn ?? $dayLogs->first())->punched_at;
            $timeOut = $dayLogs->count() > 1
                ? ($checkOut ?? $dayLogs->last())->punched_at
                : null;

            $totalHours = $timeOut
                ? $this->calculateWorkedHours($timeIn, $timeOut, $dayLogs)
                : null;
        } else {
            // Two logs/day: plain first-punch/last-punch, no break deduction.
            $timeIn = $dayLogs->first()->punched_at;
            $timeOut = $dayLogs->count() > 1 ? $dayLogs->last()->punched_at : null;

            $totalHours = $timeOut
                ? Attendance::calculateTotalHours($timeIn->format('H:i:s'), $timeOut->format('H:i:s'))
                : null;
        }

        return Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date],
            [
                'time_in' => $timeIn->format('H:i:s'),
                'time_out' => $timeOut?->format('H:i:s'),
                'total_hours' => $totalHours,
                'status' => $this->resolveStatus($timeIn, $totalHours),
                'remarks' => self::BIOMETRIC_REMARK,
            ]
        );
    }

    /**
     * total time between time_in and time_out, minus any complete
     * break-out -> break-in intervals logged in between.
     *
     * @param  \Illuminate\Support\Collection<int, ZkAttendanceLog>  $dayLogs
     */
    protected function calculateWorkedHours(Carbon $timeIn, Carbon $timeOut, $dayLogs): ?float
    {
        $grossHours = Attendance::calculateTotalHours($timeIn->format('H:i:s'), $timeOut->format('H:i:s'));

        if ($grossHours === null) {
            return null;
        }

        $breakMinutes = 0;
        $breakStartedAt = null;

        foreach ($dayLogs as $log) {
            if ($log->status === self::STATUS_BREAK_OUT) {
                $breakStartedAt = $log->punched_at;
            } elseif ($log->status === self::STATUS_BREAK_IN && $breakStartedAt) {
                $breakMinutes += $breakStartedAt->diffInMinutes($log->punched_at, true);
                $breakStartedAt = null;
            }
        }

        return $breakMinutes > 0
            ? max(0, round($grossHours - ($breakMinutes / 60), 2))
            : $grossHours;
    }

    protected function resolveStatus(Carbon $timeIn, ?float $totalHours): string
    {
        $shiftStart = Carbon::parse($timeIn->toDateString().' '.config('attendance.shift_start'));
        $graceMinutes = (int) config('attendance.late_grace_minutes');

        if ($timeIn->greaterThan($shiftStart->copy()->addMinutes($graceMinutes))) {
            return AttendanceStatus::Late->value;
        }

        if ($totalHours !== null && $totalHours < (float) config('attendance.half_day_max_hours')) {
            return AttendanceStatus::HalfDay->value;
        }

        return AttendanceStatus::Present->value;
    }

    protected function parseTimestamp(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
