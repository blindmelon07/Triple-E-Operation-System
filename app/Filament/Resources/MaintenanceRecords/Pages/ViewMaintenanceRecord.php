<?php

namespace App\Filament\Resources\MaintenanceRecords\Pages;

use App\Filament\Resources\MaintenanceRecords\Actions\RequestDateChangeAction;
use App\Filament\Resources\MaintenanceRecords\MaintenanceRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenanceRecord extends ViewRecord
{
    protected static string $resource = MaintenanceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RequestDateChangeAction::make(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
