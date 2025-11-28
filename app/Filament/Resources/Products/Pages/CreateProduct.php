<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $product = parent::handleRecordCreation($data);

        // Save inventory quantity
        $product->inventory()->create([
            'quantity' => $data['quantity'] ?? 0,
        ]);

        return $product;
    }
}
