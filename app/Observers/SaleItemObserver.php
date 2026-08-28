<?php

namespace App\Observers;

use App\Models\InventoryMovement;
use App\Models\SaleItem;

class SaleItemObserver
{
    public function created(SaleItem $saleItem): void
    {
        // Skip inventory movement for manual items (no product)
        if ($saleItem->is_manual || !$saleItem->product_id) {
            return;
        }

        // Inventory (and every other movement source, e.g. Purchases) is tracked
        // in the product's base unit — convert here too, so this log matches the
        // quantity actually deducted from stock rather than the unit the
        // customer bought in (e.g. "1 box" of a product sold by the kilo).
        $product = $saleItem->product;
        $baseQuantity = $saleItem->quantity * ($product?->conversionFactorFor($saleItem->unit) ?? 1);

        $notes = 'Sold via POS';
        if ($product && $saleItem->unit !== $product->unit->value) {
            $notes .= " ({$saleItem->quantity} {$saleItem->unit})";
        }

        // Log inventory movement when a sale item is created
        InventoryMovement::create([
            'product_id' => $saleItem->product_id,
            'type' => 'out',
            'quantity' => $baseQuantity,
            'reason' => 'Sale',
            'reference_id' => $saleItem->sale_id,
            'reference_type' => 'App\Models\Sale',
            'notes' => $notes,
        ]);
    }
}
