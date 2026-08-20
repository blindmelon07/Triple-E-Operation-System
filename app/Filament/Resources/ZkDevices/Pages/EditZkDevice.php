<?php

namespace App\Filament\Resources\ZkDevices\Pages;

use App\Filament\Resources\ZkDevices\ZkDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditZkDevice extends EditRecord
{
    protected static string $resource = ZkDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
