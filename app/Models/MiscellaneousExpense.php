<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MiscellaneousExpense extends Model
{
    use Auditable;

    protected $fillable = [
        'vehicle_id',
        'user_id',
        'reference_number',
        'expense_date',
        'description',
        'payee',
        'si_dr_number',
        'amount',
        'notes',
        'receipt_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
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
     * Generate a unique reference number, e.g. MISC-20261106-0001.
     */
    public static function generateReferenceNumber(): string
    {
        $prefix = 'MISC';
        $date = now()->format('Ymd');
        $last = static::whereDate('created_at', today())->latest('id')->first();
        $sequence = $last ? ((int) substr($last->reference_number ?? '0000', -4)) + 1 : 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}
