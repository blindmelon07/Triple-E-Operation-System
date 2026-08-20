<?php

namespace App\Filament\Resources\ZkDevices\Pages;

use App\Filament\Resources\ZkDevices\ZkDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListZkDevices extends ListRecords
{
    protected static string $resource = ZkDeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Register Device'),
        ];
    }
}
