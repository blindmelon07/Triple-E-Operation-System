<?php

namespace App\Filament\Resources\FuelLogs\Pages;

use App\Filament\Resources\FuelLogs\FuelLogResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateFuelLog extends CreateRecord
{
    protected static string $resource = FuelLogResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // Calculate total cost if not set
        if (! isset($data['cost']) || $data['cost'] == 0) {
            $data['cost'] = ($data['liters'] ?? 0) * ($data['price_per_liter'] ?? 0);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Update vehicle mileage if the fuel-up odometer reading is higher
        $record = $this->record;
        if ($record->vehicle && $record->odometer_reading && $record->odometer_reading > $record->vehicle->current_mileage) {
            $record->vehicle->update(['current_mileage' => $record->odometer_reading]);
        }
    }
}
