<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'product_id', 'custom_item_name', 'quantity', 'quantity_received', 'unit', 'price'];

    /** @use HasFactory<\Database\Factories\PurchaseItemFactory> */
    use HasFactory;

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Display name for the line: the catalog product, or the free-text
     * name for a custom item.
     */
    public function getItemNameAttribute(): ?string
    {
        return $this->custom_item_name ?? $this->product?->name;
    }

    public function purchase(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    protected static function booted(): void
    {
        static::created(function (PurchaseItem $item) {
            $received = $item->quantity_received ?? 0;
            if ($received > 0 && $item->product_id) {
                $item->adjustInventory($item->product_id, $received);
                $item->logInventoryMovement('in', $received, 'Purchase received');
            }

            $item->recalculatePurchaseTotal();
        });

        static::updated(function (PurchaseItem $item) {
            // Skip if the record was just created in this request — inventory
            // was already updated by the created event, so don't double-count.
            if ($item->wasRecentlyCreated) {
                $item->recalculatePurchaseTotal();

                return;
            }

            $oldReceived = $item->getOriginal('quantity_received') ?? 0;
            $newReceived = $item->quantity_received ?? 0;

            if ($item->isDirty('product_id')) {
                // Product swapped on the line: move the received stock off
                // the old product and onto the new one. Either side may be
                // null when switching to/from a custom (non-catalog) item.
                $oldProductId = $item->getOriginal('product_id');

                if ($oldReceived > 0 && $oldProductId) {
                    $item->adjustInventory($oldProductId, -$oldReceived);
                    $item->logInventoryMovement('out', $oldReceived, 'Purchase item product changed', $oldProductId);
                }

                if ($newReceived > 0 && $item->product_id) {
                    $item->adjustInventory($item->product_id, $newReceived);
                    $item->logInventoryMovement('in', $newReceived, 'Purchase item product changed');
                }
            } elseif ($item->isDirty('quantity_received')) {
                $difference = $newReceived - $oldReceived;

                if ($difference != 0 && $item->product_id) {
                    $item->adjustInventory($item->product_id, $difference);

                    $item->logInventoryMovement(
                        $difference > 0 ? 'in' : 'out',
                        abs($difference),
                        'Purchase receipt adjusted'
                    );
                }
            }

            $item->recalculatePurchaseTotal();
        });

        static::deleted(function (PurchaseItem $item) {
            $received = $item->quantity_received ?? 0;
            if ($received > 0 && $item->product_id) {
                $item->adjustInventory($item->product_id, -$received);
                $item->logInventoryMovement('out', $received, 'Purchase item deleted');
            }

            $item->recalculatePurchaseTotal();
        });
    }

    /**
     * Log a stock movement caused by this purchase item so it shows up
     * in the Inventory In/Out report.
     */
    protected function logInventoryMovement(string $type, float $quantity, string $reason, ?int $productId = null): void
    {
        InventoryMovement::create([
            'product_id' => $productId ?? $this->product_id,
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
            'reference_id' => $this->purchase_id,
            'reference_type' => Purchase::class,
            'notes' => 'Recorded via Purchase #'.$this->purchase_id,
        ]);
    }

    /**
     * Add (or with a negative delta, remove) stock for a product. Looked up
     * by id rather than $this->product so a stale cached relation can't
     * point at the wrong product after product_id changes.
     */
    protected function adjustInventory(int $productId, float $delta): void
    {
        $inventory = Inventory::where('product_id', $productId)->first();

        if ($inventory) {
            $inventory->increment('quantity', $delta);
        } elseif ($delta > 0) {
            Inventory::create([
                'product_id' => $productId,
                'quantity' => $delta,
            ]);
        }
    }

    /**
     * Recalculate the parent purchase total
     */
    public function recalculatePurchaseTotal(): void
    {
        $purchase = $this->purchase;
        if ($purchase) {
            $total = $purchase->purchase_items()->get()->sum(function ($item) {
                return ($item->price ?? 0) * ($item->quantity ?? 0);
            });
            $purchase->updateQuietly(['total' => $total]);
        }
    }
}
