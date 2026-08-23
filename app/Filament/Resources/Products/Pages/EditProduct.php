<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $quantity = (float) ($this->data['quantity'] ?? 0);
        $previousQuantity = (float) ($this->record->inventory?->quantity ?? 0);

        $this->record->inventory()->updateOrCreate(
            ['product_id' => $this->record->id],
            ['quantity' => $quantity]
        );

        $difference = $quantity - $previousQuantity;

        if ($difference !== 0.0) {
            InventoryMovement::create([
                'product_id' => $this->record->id,
                'type' => $difference > 0 ? 'in' : 'out',
                'quantity' => abs($difference),
                'reason' => 'Manual Stock Adjustment',
                'reference_id' => $this->record->id,
                'reference_type' => Product::class,
                'notes' => 'Stock edited via Product form by '.(auth()->user()?->name ?? 'Unknown').
                    " ({$previousQuantity} → {$quantity})",
            ]);
        }
    }
}
