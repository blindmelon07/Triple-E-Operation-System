<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'quantity', 'quantity_received', 'unit', 'price'];

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
            $received = $item->quantity_received ?? 0;
            if ($received > 0) {
                $inventory = $item->product->inventory;
                if ($inventory) {
                    $inventory->increment('quantity', $received);
                } else {
                    Inventory::create([
                        'product_id' => $item->product_id,
                        'quantity' => $received,
                    ]);
                }
            }

            $item->recalculatePurchaseTotal();
        });

        static::updated(function (PurchaseItem $item) {
            if ($item->isDirty('quantity_received')) {
                $oldReceived = $item->getOriginal('quantity_received') ?? 0;
                $newReceived = $item->quantity_received ?? 0;
                $difference = $newReceived - $oldReceived;

                if ($difference !== 0) {
                    $inventory = $item->product->inventory;
                    if ($inventory) {
                        $inventory->increment('quantity', $difference);
                    } elseif ($difference > 0) {
                        Inventory::create([
                            'product_id' => $item->product_id,
                            'quantity' => $difference,
                        ]);
                    }
                }
            }

            $item->recalculatePurchaseTotal();
        });

        static::deleted(function (PurchaseItem $item) {
            $received = $item->quantity_received ?? 0;
            if ($received > 0) {
                $inventory = $item->product->inventory;
                if ($inventory) {
                    $inventory->decrement('quantity', $received);
                }
            }

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
                return ($item->price ?? 0) * ($item->quantity_received ?? 0);
            });
            $purchase->updateQuietly(['total' => $total]);
        }
    }
}
