<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Traits\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'time_in',
        'break_out',
        'break_in',
        'time_out',
        'total_hours',
        'status',
        'remarks',
        'recorded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_hours' => 'decimal:2',
            'status' => AttendanceStatus::class,
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
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Auto-calculate total hours from time_in and time_out.
     */
    public static function calculateTotalHours(?string $timeIn, ?string $timeOut): ?float
    {
        if (! $timeIn || ! $timeOut) {
            return null;
        }

        $in = Carbon::parse($timeIn);
        $out = Carbon::parse($timeOut);

        if ($out->lessThan($in)) {
            return null;
        }

        return round($in->diffInMinutes($out, true) / 60, 2);
    }

    /**
     * Worked hours for a 4-log day: time in to time out, minus the break
     * when both break punches exist.
     */
    public static function calculateWorkedHours(?string $timeIn, ?string $breakOut, ?string $breakIn, ?string $timeOut): ?float
    {
        $gross = static::calculateTotalHours($timeIn, $timeOut);

        if ($gross === null) {
            return null;
        }

        $break = static::calculateTotalHours($breakOut, $breakIn) ?? 0;

        return max(0, round($gross - $break, 2));
    }
}
