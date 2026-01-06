<?php

namespace App\Filament\Resources\MaintenanceRequests\Pages;

use App\Filament\Resources\MaintenanceRequests\MaintenanceRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMaintenanceRequest extends CreateRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['requested_by'] = Auth::id();
        $data['status'] = 'pending';

        return $data;
    }
}
