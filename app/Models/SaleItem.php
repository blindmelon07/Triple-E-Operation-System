<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_description',
        'unit',
        'unit_price',
        'discount_amount',
        'discount_is_flat',
        'is_manual',
        'quantity',
        'price',
        'is_voided',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'is_manual' => 'boolean',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_is_flat' => 'boolean',
        'price' => 'decimal:2',
        'is_voided' => 'boolean',
        'voided_at' => 'datetime',
    ];

    /** @use HasFactory<\Database\Factories\SaleItemFactory> */
    use HasFactory;

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function sale(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Items that still count toward the sale's total — excludes any line
     * that was individually voided. Use this (not the bare relation) when
     * displaying receipts/reports so totals stay consistent.
     */
    public function scopeActive($query)
    {
        return $query->where('is_voided', false);
    }

    protected static function booted(): void
    {
        static::created(function (SaleItem $item) {
            // Only decrement inventory for non-manual items with a product
            if (!$item->is_manual && $item->product) {
                $inventory = $item->product->inventory;
                if ($inventory) {
                    $baseQuantity = ($item->quantity ?? 1) * $item->product->conversionFactorFor($item->unit);
                    $inventory->decrement('quantity', $baseQuantity);
                }
            }
        });
    }
}
