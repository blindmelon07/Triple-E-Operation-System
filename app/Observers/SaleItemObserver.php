<?php

namespace App\Observers;

use App\Models\InventoryMovement;
use App\Models\SaleItem;

class SaleItemObserver
{
    public function created(SaleItem $saleItem): void
    {
        // Log inventory movement when a sale item is created
        InventoryMovement::create([
            'product_id' => $saleItem->product_id,
            'type' => 'out',
            'quantity' => $saleItem->quantity,
            'reason' => 'Sale',
            'reference_id' => $saleItem->sale_id,
            'reference_type' => 'App\Models\Sale',
            'notes' => 'Sold via POS',
        ]);
    }
}
