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
     * Punches by the same person closer together than this are one scan
     * registered twice, and only the first is used.
     */
    protected const DUPLICATE_PUNCH_MINUTES = 2;

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
     * Make sure every user enrolled on a device has an Employee with their
     * PIN, so punches map to someone without manual setup. An employee who
     * already has a PIN is never touched (a manual mapping wins); otherwise
     * a PIN-less employee with exactly the same name gets the PIN, and only
     * if there's none is a new device-only Employee created. Setting the PIN
     * fires Employee's reconcile hook, so earlier punches get folded in too.
     *
     * @param  array<int, array{pin: string, name: ?string}>  $deviceUsers
     * @return array{created: int, linked: int, unchanged: int}
     */
    public function syncDeviceUsers(array $deviceUsers): array
    {
        $result = ['created' => 0, 'linked' => 0, 'unchanged' => 0];

        foreach ($deviceUsers as $deviceUser) {
            $pin = trim((string) $deviceUser['pin']);
            $name = trim((string) ($deviceUser['name'] ?? '')) ?: "Device User {$pin}";

            if ($pin === '' || Employee::where('biometric_pin', $pin)->exists()) {
                $result['unchanged']++;

                continue;
            }

            $sameName = Employee::whereNull('biometric_pin')
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->get();

            if ($sameName->count() === 1) {
                $sameName->first()->update(['biometric_pin' => $pin]);
                $result['linked']++;
            } else {
                Employee::create([
                    'name' => $name,
                    'biometric_pin' => $pin,
                    'attendance_log_mode' => AttendanceLogMode::Four,
                    'is_active' => true,
                ]);
                $result['created']++;
            }
        }

        if ($result['created'] || $result['linked']) {
            Log::info('ZKTeco: synced device users', $result);
        }

        return $result;
    }

    /**
     * Run reconcileUnmappedPunches() for every employee with a PIN that has
     * stranded punches waiting. Returns how many punches got linked.
     */
    public function reconcileAllUnmappedPunches(): int
    {
        $pins = ZkAttendanceLog::whereNull('employee_id')->distinct()->pluck('pin');

        if ($pins->isEmpty()) {
            return 0;
        }

        $linked = 0;

        foreach (Employee::whereIn('biometric_pin', $pins)->get() as $employee) {
            $linked += ZkAttendanceLog::whereNull('employee_id')->where('pin', $employee->biometric_pin)->count();
            $this->reconcileUnmappedPunches($employee);
        }

        return $linked;
    }

    /**
     * Recompute the day's Attendance row for an employee from all of that
     * day's raw logs. How time_in/time_out/total_hours are derived depends
     * on the employee's AttendanceLogMode:
     *  - Two:  first punch of the day = time_in, last = time_out. No break
     *          is subtracted (the employee isn't expected to punch for one).
     *  - Four: punches in order are time_in, break_out, break_in, time_out;
     *          the break is excluded from total_hours.
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

        // A face/finger scan is often registered twice in a row; only the
        // first of a burst counts, otherwise a double scan at time-in would
        // be taken as the break-out punch.
        $punches = [];
        foreach ($dayLogs as $log) {
            $last = end($punches);
            if ($last === false || $last->diffInMinutes($log->punched_at, true) >= self::DUPLICATE_PUNCH_MINUTES) {
                $punches[] = $log->punched_at;
            }
        }

        $breakOut = $breakIn = null;
        $logMode = $employee->attendance_log_mode ?? AttendanceLogMode::Four;

        if ($logMode === AttendanceLogMode::Four) {
            // Punch order decides the meaning, not the device's IN/OUT key
            // state (staff rarely press those keys on a face terminal):
            //   1 punch   -> in
            //   2 punches -> in, break out (on break)
            //   3 punches -> in, break out, break in (back, not yet out)
            //   4+        -> in, break out, break in, ..., out (last)
            // The day stays open (no total hours) until the 4th punch.
            $timeIn = $punches[0];
            $count = count($punches);
            $breakOut = $punches[1] ?? null;
            $breakIn = $punches[2] ?? null;
            $timeOut = $count >= 4 ? $punches[$count - 1] : null;
        } else {
            // Two logs/day: plain first-punch/last-punch, no break deduction.
            $timeIn = $punches[0];
            $timeOut = count($punches) > 1 ? end($punches) : null;
        }

        $totalHours = Attendance::calculateWorkedHours(
            $timeIn->format('H:i:s'),
            $breakOut?->format('H:i:s'),
            $breakIn?->format('H:i:s'),
            $timeOut?->format('H:i:s'),
        );

        return Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date],
            [
                'time_in' => $timeIn->format('H:i:s'),
                'break_out' => $breakOut?->format('H:i:s'),
                'break_in' => $breakIn?->format('H:i:s'),
                'time_out' => $timeOut?->format('H:i:s'),
                'total_hours' => $totalHours,
                'status' => $this->resolveStatus($timeIn, $totalHours),
                'remarks' => self::BIOMETRIC_REMARK,
            ]
        );
    }

    /**
     * Re-derive every device-sourced Attendance day from its raw punches,
     * e.g. after the folding rules change. Manually managed days are left
     * alone (foldIntoAttendance skips them). Returns the number of days.
     */
    public function recalculateBiometricAttendance(?Carbon $since = null): int
    {
        $days = 0;

        Attendance::where('remarks', self::BIOMETRIC_REMARK)
            ->when($since, fn ($query) => $query->whereDate('date', '>=', $since->toDateString()))
            ->with('employee')
            ->chunkById(200, function ($attendances) use (&$days) {
                foreach ($attendances as $attendance) {
                    if ($attendance->employee) {
                        $this->foldIntoAttendance($attendance->employee, Carbon::parse($attendance->date->toDateString().' 12:00:00'));
                        $days++;
                    }
                }
            });

        return $days;
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
