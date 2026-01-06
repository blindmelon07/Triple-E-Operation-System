<?php

namespace App\Filament\Resources\MaintenanceRecords\Pages;

use App\Filament\Resources\MaintenanceRecords\MaintenanceRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceRecord extends EditRecord
{
    protected static string $resource = MaintenanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Calculate total cost
        $data['cost'] = ($data['parts_cost'] ?? 0) + ($data['labor_cost'] ?? 0);

        return $data;
    }

    protected function afterSave(): void
    {
        // Update vehicle mileage if the service mileage is higher
        $record = $this->record;
        if ($record->vehicle && $record->mileage_at_service > $record->vehicle->current_mileage) {
            $record->vehicle->update(['current_mileage' => $record->mileage_at_service]);
        }
    }
}
