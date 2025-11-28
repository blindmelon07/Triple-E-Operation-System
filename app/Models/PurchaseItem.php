<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'quantity', 'price'];
    /** @use HasFactory<\Database\Factories\PurchaseItemFactory> */
    use HasFactory;
    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchase(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    protected static function booted(): void
    {
        static::created(function (PurchaseItem $item) {
            $inventory = $item->product->inventory;
            if ($inventory) {
                $inventory->increment('quantity', $item->quantity ?? 1);
            }
        });
    }
}
