<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'quantity', 'price'];
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

    protected static function booted(): void
    {
        static::created(function (SaleItem $item) {
            $inventory = $item->product->inventory;
            if ($inventory) {
                $inventory->decrement('quantity', $item->quantity ?? 1);
            }
        });
    }
}
