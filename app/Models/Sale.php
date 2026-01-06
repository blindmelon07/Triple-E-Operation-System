<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'date',
        'total',
        'payment_status',
        'amount_paid',
        'due_date',
        'paid_date',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'paid_date' => 'date',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Sale $sale) {
            if (! $sale->due_date && $sale->customer_id) {
                $customer = Customer::find($sale->customer_id);
                if ($customer && $customer->payment_term_days > 0) {
                    $saleDate = $sale->date ?? now();
                    $sale->due_date = $saleDate->copy()->addDays($customer->payment_term_days);
                }
            }
        });
    }

    public function sale_items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) $this->total - (float) $this->amount_paid;
    }

    public function getDaysOverdueAttribute(): ?int
    {
        if (! $this->due_date || $this->payment_status === 'paid') {
            return null;
        }

        $daysOverdue = now()->diffInDays($this->due_date, false);

        return $daysOverdue < 0 ? abs($daysOverdue) : null;
    }

    public function getAgingBucketAttribute(): string
    {
        $daysOverdue = $this->days_overdue;

        if ($daysOverdue === null) {
            return 'Current';
        }

        return match (true) {
            $daysOverdue <= 30 => '1-30 Days',
            $daysOverdue <= 60 => '31-60 Days',
            $daysOverdue <= 90 => '61-90 Days',
            default => 'Over 90 Days',
        };
    }
}
