<?php

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'category_id', 'supplier_id', 'price', 'cost_price', 'quantity', 'unit'];

    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unit' => ProductUnit::class,
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
        ];
    }

    /**
     * Get the profit margin for this product.
     */
    public function getProfitMarginAttribute(): float
    {
        if (! $this->price || $this->price == 0) {
            return 0;
        }

        $cost = $this->cost_price ?? ($this->price * 0.7); // Default to 70% of price if no cost set

        return (($this->price - $cost) / $this->price) * 100;
    }

    /**
     * Get the profit per unit for this product.
     */
    public function getProfitPerUnitAttribute(): float
    {
        $cost = $this->cost_price ?? ($this->price * 0.7);

        return (float) $this->price - (float) $cost;
    }

    public function inventory(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function saleItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
