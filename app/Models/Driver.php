<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'license_number',
        'vehicle_type',
        'vehicle_plate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function completedDeliveries(): HasMany
    {
        return $this->hasMany(Delivery::class)->where('status', 'delivered');
    }

    public function getDeliveryCountAttribute(): int
    {
        return $this->deliveries()->where('status', 'delivered')->count();
    }

    public function getAverageRatingAttribute(): ?float
    {
        return $this->deliveries()->whereNotNull('rating')->avg('rating');
    }

    public function getSuccessRateAttribute(): float
    {
        $total = $this->deliveries()->whereIn('status', ['delivered', 'failed', 'returned'])->count();
        if ($total === 0) {
            return 0;
        }
        $successful = $this->deliveries()->where('status', 'delivered')->count();

        return round(($successful / $total) * 100, 2);
    }
}
