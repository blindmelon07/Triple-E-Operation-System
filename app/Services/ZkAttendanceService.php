<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\User;
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

        $user = User::where('biometric_pin', $pin)->first();

        $log = ZkAttendanceLog::firstOrCreate(
            [
                'zk_device_id' => $device->id,
                'pin' => $pin,
                'punched_at' => $punchedAt,
            ],
            [
                'user_id' => $user?->id,
                'status' => $status,
                'verify_type' => $verify,
                'raw_line' => $line,
            ]
        );

        if ($user) {
            $attendance = $this->foldIntoAttendance($user, $punchedAt);
            if ($attendance && $log->attendance_id !== $attendance->id) {
                $log->update(['attendance_id' => $attendance->id]);
            }
        } else {
            Log::warning('ZKTeco: punch from unmapped PIN', ['pin' => $pin, 'device' => $device->serial_number]);
        }

        return $log;
    }

    /**
     * Recompute the day's Attendance row for a user from all of that day's
     * raw logs: earliest check-in punch becomes time_in, latest check-out
     * punch becomes time_out, and any break-out/break-in pairs in between
     * are excluded from total_hours instead of being counted as worked time.
     *
     * Never touches an Attendance row an admin created/edited by hand (i.e.
     * one that doesn't carry our own BIOMETRIC_REMARK marker) — a stray or
     * retransmitted punch must not clobber a manual correction like on_leave.
     */
    protected function foldIntoAttendance(User $user, Carbon $punchedAt): ?Attendance
    {
        $date = $punchedAt->toDateString();

        $existing = Attendance::where('user_id', $user->id)->where('date', $date)->first();

        if ($existing && $existing->remarks !== self::BIOMETRIC_REMARK) {
            Log::info('ZKTeco: not overwriting manually-managed Attendance record', [
                'user_id' => $user->id,
                'date' => $date,
                'attendance_id' => $existing->id,
            ]);

            return $existing;
        }

        $dayLogs = ZkAttendanceLog::where('user_id', $user->id)
            ->whereDate('punched_at', $date)
            ->orderBy('punched_at')
            ->get();

        if ($dayLogs->isEmpty()) {
            return null;
        }

        $checkIn = $dayLogs->firstWhere('status', self::STATUS_CHECK_IN);
        $checkOut = $dayLogs->where('status', self::STATUS_CHECK_OUT)->last();

        $timeIn = ($checkIn ?? $dayLogs->first())->punched_at;
        $timeOut = $dayLogs->count() > 1
            ? ($checkOut ?? $dayLogs->last())->punched_at
            : null;

        $totalHours = $timeOut
            ? $this->calculateWorkedHours($timeIn, $timeOut, $dayLogs)
            : null;

        return Attendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $date],
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
