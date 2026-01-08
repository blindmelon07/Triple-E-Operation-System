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
            // Update inventory
            $inventory = $item->product->inventory;
            if ($inventory) {
                $inventory->increment('quantity', $item->quantity ?? 1);
            } else {
                // Create inventory record if it doesn't exist
                Inventory::create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity ?? 1,
                ]);
            }

            // Recalculate purchase total
            $item->recalculatePurchaseTotal();
        });

        static::updated(function (PurchaseItem $item) {
            // Handle inventory changes if quantity changed
            if ($item->isDirty('quantity')) {
                $oldQuantity = $item->getOriginal('quantity') ?? 0;
                $newQuantity = $item->quantity ?? 0;
                $difference = $newQuantity - $oldQuantity;

                $inventory = $item->product->inventory;
                if ($inventory && $difference !== 0) {
                    $inventory->increment('quantity', $difference);
                }
            }

            // Recalculate purchase total
            $item->recalculatePurchaseTotal();
        });

        static::deleted(function (PurchaseItem $item) {
            // Reduce inventory when item is deleted
            $inventory = $item->product->inventory;
            if ($inventory) {
                $inventory->decrement('quantity', $item->quantity ?? 0);
            }

            // Recalculate purchase total
            $item->recalculatePurchaseTotal();
        });
    }

    /**
     * Recalculate the parent purchase total
     */
    public function recalculatePurchaseTotal(): void
    {
        $purchase = $this->purchase;
        if ($purchase) {
            $total = $purchase->purchase_items()->get()->sum(function ($item) {
                return ($item->price ?? 0) * ($item->quantity ?? 1);
            });
            $purchase->updateQuietly(['total' => $total]);
        }
    }
}
