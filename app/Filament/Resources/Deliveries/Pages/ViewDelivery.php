<?php

namespace App\Filament\Resources\Deliveries\Pages;

use App\Filament\Resources\Deliveries\DeliveryResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDelivery extends ViewRecord
{
    protected static string $resource = DeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print Receipt')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => route('delivery.print-receipt', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
