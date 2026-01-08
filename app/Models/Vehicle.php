<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleFactory> */
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'make',
        'model',
        'year',
        'color',
        'vin',
        'engine_number',
        'fuel_type',
        'transmission',
        'current_mileage',
        'acquisition_date',
        'acquisition_cost',
        'status',
        'assigned_driver_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
            'year' => 'integer',
            'current_mileage' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }

    /**
     * @return HasMany<MaintenanceRecord, $this>
     */
    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    /**
     * Get the full vehicle name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->year} {$this->make} {$this->model}";
    }

    /**
     * Get the display name with plate.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->plate_number} - {$this->full_name}";
    }

    /**
     * Get total maintenance cost.
     */
    public function getTotalMaintenanceCostAttribute(): float
    {
        return (float) $this->maintenanceRecords()->sum('cost');
    }

    /**
     * Get last maintenance date.
     */
    public function getLastMaintenanceDateAttribute(): ?\Carbon\Carbon
    {
        return $this->maintenanceRecords()
            ->where('status', 'completed')
            ->latest('maintenance_date')
            ->value('maintenance_date');
    }

    /**
     * Check if maintenance is due based on mileage or time.
     */
    public function getMaintenanceDueAttribute(): bool
    {
        $lastRecord = $this->maintenanceRecords()
            ->where('status', 'completed')
            ->latest('maintenance_date')
            ->first();

        if (! $lastRecord) {
            return true;
        }

        if ($lastRecord->next_service_mileage && $this->current_mileage >= $lastRecord->next_service_mileage) {
            return true;
        }

        if ($lastRecord->next_service_date && $lastRecord->next_service_date->isPast()) {
            return true;
        }

        return false;
    }
}
