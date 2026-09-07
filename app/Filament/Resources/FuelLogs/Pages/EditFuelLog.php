<?php

namespace App\Filament\Resources\FuelLogs\Pages;

use App\Filament\Resources\FuelLogs\FuelLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFuelLog extends EditRecord
{
    protected static string $resource = FuelLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['cost'] = ($data['liters'] ?? 0) * ($data['price_per_liter'] ?? 0);

        return $data;
    }

    protected function afterSave(): void
    {
        // Update vehicle mileage if the fuel-up odometer reading is higher
        $record = $this->record;
        if ($record->vehicle && $record->odometer_reading && $record->odometer_reading > $record->vehicle->current_mileage) {
            $record->vehicle->update(['current_mileage' => $record->odometer_reading]);
        }
    }
}
