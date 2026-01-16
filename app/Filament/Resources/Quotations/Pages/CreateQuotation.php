<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $total = 0;
        if (!empty($data['quotation_items'])) {
            foreach ($data['quotation_items'] as $item) {
                $total += $item['price'] ?? 0;
            }
        }
        $data['total'] = $total;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Recalculate total after items are saved
        $this->record->refresh();
        $total = $this->record->quotation_items()->sum('price');
        $this->record->updateQuietly(['total' => $total]);
    }
}
