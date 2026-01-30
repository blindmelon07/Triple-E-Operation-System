<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecord extends Model
{
    /** @use HasFactory<\Database\Factories\MaintenanceRecordFactory> */
    use HasFactory, Auditable;

    protected $fillable = [
        'vehicle_id',
        'maintenance_type_id',
        'user_id',
        'reference_number',
        'maintenance_date',
        'mileage_at_service',
        'cost',
        'parts_cost',
        'labor_cost',
        'service_provider',
        'description',
        'parts_replaced',
        'next_service_date',
        'next_service_mileage',
        'status',
        'invoice_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'maintenance_date' => 'date',
            'next_service_date' => 'date',
            'cost' => 'decimal:2',
            'parts_cost' => 'decimal:2',
            'labor_cost' => 'decimal:2',
            'mileage_at_service' => 'integer',
            'next_service_mileage' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<MaintenanceType, $this>
     */
    public function maintenanceType(): BelongsTo
    {
        return $this->belongsTo(MaintenanceType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a unique reference number.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'MNT';
        $date = now()->format('Ymd');
        $lastRecord = static::whereDate('created_at', today())->latest()->first();
        $sequence = $lastRecord ? ((int) substr($lastRecord->reference_number ?? '0000', -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    /**
     * Get total cost (parts + labor).
     */
    public function getTotalCostAttribute(): float
    {
        return (float) $this->parts_cost + (float) $this->labor_cost;
    }

    /**
     * Check if next service is due.
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->next_service_date && $this->next_service_date->isPast()) {
            return true;
        }

        if ($this->next_service_mileage && $this->vehicle && $this->vehicle->current_mileage >= $this->next_service_mileage) {
            return true;
        }

        return false;
    }
}
