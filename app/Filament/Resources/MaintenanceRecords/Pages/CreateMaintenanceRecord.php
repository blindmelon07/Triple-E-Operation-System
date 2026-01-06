<?php

namespace App\Filament\Resources\MaintenanceRecords\Pages;

use App\Filament\Resources\MaintenanceRecords\MaintenanceRecordResource;
use App\Models\Vehicle;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMaintenanceRecord extends CreateRecord
{
    protected static string $resource = MaintenanceRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        // Calculate total cost if not set
        if (! isset($data['cost']) || $data['cost'] == 0) {
            $data['cost'] = ($data['parts_cost'] ?? 0) + ($data['labor_cost'] ?? 0);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // Update vehicle mileage if the service mileage is higher
        $record = $this->record;
        if ($record->vehicle && $record->mileage_at_service > $record->vehicle->current_mileage) {
            $record->vehicle->update(['current_mileage' => $record->mileage_at_service]);
        }
    }
}
