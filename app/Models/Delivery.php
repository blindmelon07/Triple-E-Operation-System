<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Delivery extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'sale_id',
        'dr_number',
        'driver_id',
        'status',
        'assigned_at',
        'picked_up_at',
        'delivered_at',
        'delivery_address',
        'notes',
        'rating',
        'customer_feedback',
        'distance_km',
    ];

    protected static function booted(): void
    {
        // DR # is optional on the form: a hand-typed booklet number wins,
        // otherwise one is generated so every delivery still has a DR #.
        static::saving(function (Delivery $delivery) {
            if (blank($delivery->dr_number)) {
                $delivery->dr_number = static::generateDrNumber($delivery->created_at);
            }
        });
    }

    /**
     * Month-day-time in Manila time, e.g. "1005-1432" for Oct 5, 2:32 PM.
     * A "-2", "-3"... suffix is added when that minute's number is taken.
     */
    public static function generateDrNumber(?Carbon $at = null): string
    {
        $base = ($at ?? now())->copy()->timezone('Asia/Manila')->format('md-Hi');

        $number = $base;
        for ($suffix = 2; static::where('dr_number', $number)->exists(); $suffix++) {
            $number = "{$base}-{$suffix}";
        }

        return $number;
    }

    protected function casts(): array
    {
        return [
            'status' => DeliveryStatus::class,
            'assigned_at' => 'datetime',
            'picked_up_at' => 'datetime',
            'delivered_at' => 'datetime',
            'distance_km' => 'decimal:2',
            'rating' => 'integer',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function getDeliveryTimeMinutesAttribute(): ?int
    {
        if (! $this->assigned_at || ! $this->delivered_at) {
            return null;
        }

        return $this->assigned_at->diffInMinutes($this->delivered_at);
    }
}
