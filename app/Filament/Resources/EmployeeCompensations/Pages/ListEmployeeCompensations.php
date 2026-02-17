<?php

namespace App\Filament\Resources\EmployeeCompensations\Pages;

use App\Filament\Resources\EmployeeCompensations\EmployeeCompensationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeCompensations extends ListRecords
{
    protected static string $resource = EmployeeCompensationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Add Employee Compensation'),
        ];
    }
}
