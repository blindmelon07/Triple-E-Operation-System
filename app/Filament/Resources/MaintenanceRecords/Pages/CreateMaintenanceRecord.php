<?php

namespace App\Filament\Resources\MaintenanceRecords\Pages;

use App\Filament\Resources\MaintenanceRecords\MaintenanceRecordResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMaintenanceRecord extends CreateRecord
{
    protected static string $resource = MaintenanceRecordResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
