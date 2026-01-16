<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $total = 0;
        if (!empty($data['quotation_items'])) {
            foreach ($data['quotation_items'] as $item) {
                $total += $item['price'] ?? 0;
            }
        }
        $data['total'] = $total;

        return parent::handleRecordCreation($data);
    }
}
