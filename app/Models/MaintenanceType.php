<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceType extends Model
{
    /** @use HasFactory<\Database\Factories\MaintenanceTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'recommended_interval_km',
        'recommended_interval_months',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recommended_interval_km' => 'integer',
            'recommended_interval_months' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<MaintenanceRecord, $this>
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    /**
     * Get interval display text.
     */
    public function getIntervalDisplayAttribute(): string
    {
        $parts = [];

        if ($this->recommended_interval_km) {
            $parts[] = number_format($this->recommended_interval_km).' km';
        }

        if ($this->recommended_interval_months) {
            $parts[] = $this->recommended_interval_months.' months';
        }

        return count($parts) > 0 ? implode(' or ', $parts) : 'As needed';
    }
}
