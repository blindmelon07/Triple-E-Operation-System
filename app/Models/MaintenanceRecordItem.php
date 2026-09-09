<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceRecordItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'maintenance_record_id',
        'item_name',
        'unit',
        'quantity',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<MaintenanceRecord, $this>
     */
    public function maintenanceRecord(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRecord::class);
    }

    public function getAmountAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
