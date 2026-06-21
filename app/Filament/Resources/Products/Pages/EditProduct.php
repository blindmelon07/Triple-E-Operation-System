<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
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
        $quantity = $this->data['quantity'] ?? 0;

        $this->record->inventory()->updateOrCreate(
            ['product_id' => $this->record->id],
            ['quantity' => $quantity]
        );
    }
}
