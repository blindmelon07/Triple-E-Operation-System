<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ZkDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_number',
        'api_token',
        'name',
        'location',
        'is_active',
        'last_seen_at',
        'last_seen_ip',
    ];

    protected static function booted(): void
    {
        static::creating(function (ZkDevice $device) {
            $device->api_token ??= Str::random(48);
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ZkAttendanceLog, $this>
     */
    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(ZkAttendanceLog::class);
    }

    public function markSeen(?string $ip): void
    {
        $this->forceFill([
            'last_seen_at' => now(),
            'last_seen_ip' => $ip,
        ])->save();
    }
}
