<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchase extends CreateRecord
{
    protected static string $resource = PurchaseResource::class;

    // Total is derived from the line items, so it has to be computed after
    // Filament has actually persisted the purchase_items relationship
    // (saveRelationships() runs after handleRecordCreation) — computing it
    // from the raw form $data beforehand is fragile and can silently save 0.
    protected function afterCreate(): void
    {
        $this->recalculateTotal();
    }

    private function recalculateTotal(): void
    {
        $total = $this->record->purchase_items()
            ->get()
            ->sum(fn ($item) => (float) $item->price * (float) $item->quantity);

        $this->record->update(['total' => $total]);
    }
}
