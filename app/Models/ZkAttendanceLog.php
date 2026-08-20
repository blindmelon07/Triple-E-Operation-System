<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZkAttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'zk_device_id',
        'pin',
        'employee_id',
        'attendance_id',
        'punched_at',
        'status',
        'verify_type',
        'raw_line',
    ];

    protected function casts(): array
    {
        return [
            'punched_at' => 'datetime',
            'status' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<ZkDevice, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(ZkDevice::class, 'zk_device_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
