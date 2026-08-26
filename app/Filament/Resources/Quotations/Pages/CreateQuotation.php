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
        // Recalculate total after items are saved. Relationship-backed repeaters
        // (quotation_items) aren't present in mutateFormDataBeforeCreate's $data,
        // so total (and the down payment clamp, which depends on it) can only be
        // computed correctly here, once the items actually exist in the database.
        $this->record->refresh();
        $total = $this->record->quotation_items()->sum('price');
        $downPayment = min((float) $this->record->down_payment, $total);
        $this->record->updateQuietly(['total' => $total, 'down_payment' => $downPayment]);
    }
}
