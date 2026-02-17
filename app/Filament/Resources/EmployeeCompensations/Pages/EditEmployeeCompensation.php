<?php

namespace App\Filament\Resources\EmployeeCompensations\Pages;

use App\Filament\Resources\EmployeeCompensations\EmployeeCompensationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeeCompensation extends EditRecord
{
    protected static string $resource = EmployeeCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
