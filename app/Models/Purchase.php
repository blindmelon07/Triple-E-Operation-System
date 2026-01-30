<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory, Auditable;

    protected $fillable = [
        'supplier_id',
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
        static::creating(function (Purchase $purchase) {
            if (! $purchase->due_date && $purchase->supplier_id) {
                $supplier = Supplier::find($purchase->supplier_id);
                if ($supplier && $supplier->payment_term_days > 0) {
                    $purchaseDate = $purchase->date ?? now();
                    $purchase->due_date = $purchaseDate->copy()->addDays($supplier->payment_term_days);
                }
            }
        });
    }

    public function purchase_items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
