<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
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
