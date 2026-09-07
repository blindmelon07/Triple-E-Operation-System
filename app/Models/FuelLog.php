<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    /** @use HasFactory<\Database\Factories\FuelLogFactory> */
    use HasFactory, Auditable;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'reference_number',
        'fuel_date',
        'odometer_reading',
        'liters',
        'price_per_liter',
        'cost',
        'fuel_station',
        'notes',
        'receipt_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fuel_date' => 'date',
            'liters' => 'decimal:2',
            'price_per_liter' => 'decimal:2',
            'cost' => 'decimal:2',
            'odometer_reading' => 'integer',
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
        $prefix = 'FUEL';
        $date = now()->format('Ymd');
        $lastLog = static::whereDate('created_at', today())->latest()->first();
        $sequence = $lastLog ? ((int) substr($lastLog->reference_number ?? '0000', -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
