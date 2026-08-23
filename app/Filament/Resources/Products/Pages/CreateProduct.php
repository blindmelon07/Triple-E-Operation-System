<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\InventoryMovement;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $product = parent::handleRecordCreation($data);

        $quantity = (float) ($data['quantity'] ?? 0);

        // Save inventory quantity
        $product->inventory()->create([
            'quantity' => $quantity,
        ]);

        if ($quantity > 0) {
            InventoryMovement::create([
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => $quantity,
                'reason' => 'Initial Stock',
                'reference_id' => $product->id,
                'reference_type' => \App\Models\Product::class,
                'notes' => 'Initial stock set on product creation by '.(auth()->user()?->name ?? 'Unknown'),
            ]);
        }

        return $product;
    }
}
