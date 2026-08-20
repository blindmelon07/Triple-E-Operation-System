<?php

namespace App\Models;

use App\Enums\AttendanceLogMode;
use App\Services\ZkAttendanceService;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The HR/attendance master record. Every employee gets one row here,
 * whether or not they ever log into TOS — a User login (via user_id) is
 * optional and only needed for staff who actually use the admin panel.
 */
class Employee extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'biometric_pin',
        'attendance_log_mode',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attendance_log_mode' => AttendanceLogMode::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // A biometric_pin set/changed after punches from that PIN already
        // arrived (device enrolled + punching before payroll got around to
        // mapping the employee) shouldn't leave that history stranded. This
        // covers both editing an existing Employee's PIN and creating a new
        // device-only Employee with the PIN already filled in.
        $reconcile = function (Employee $employee) {
            if ($employee->biometric_pin) {
                app(ZkAttendanceService::class)->reconcileUnmappedPunches($employee);
            }
        };

        static::created($reconcile);

        static::updated(function (Employee $employee) use ($reconcile) {
            if ($employee->wasChanged('biometric_pin')) {
                $reconcile($employee);
            }
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Attendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * @return HasMany<PayrollItem, $this>
     */
    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * @return HasMany<ZkAttendanceLog, $this>
     */
    public function zkAttendanceLogs(): HasMany
    {
        return $this->hasMany(ZkAttendanceLog::class);
    }

    /**
     * @return HasOne<EmployeeCompensation, $this>
     */
    public function employeeCompensation(): HasOne
    {
        return $this->hasOne(EmployeeCompensation::class);
    }
}
