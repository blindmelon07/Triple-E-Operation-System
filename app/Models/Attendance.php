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
    use HasFactory, Auditable;

    protected $fillable = [
        'user_id',
        'date',
        'time_in',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

        return round($out->diffInMinutes($in) / 60, 2);
    }
}
