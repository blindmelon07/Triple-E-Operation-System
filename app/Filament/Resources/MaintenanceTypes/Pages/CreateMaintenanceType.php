<?php

namespace App\Filament\Resources\MaintenanceTypes\Pages;

use App\Filament\Resources\MaintenanceTypes\MaintenanceTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceType extends CreateRecord
{
    protected static string $resource = MaintenanceTypeResource::class;
}
