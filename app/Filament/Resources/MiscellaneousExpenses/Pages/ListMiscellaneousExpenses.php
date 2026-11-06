<?php

namespace App\Filament\Resources\MiscellaneousExpenses\Pages;

use App\Filament\Resources\MiscellaneousExpenses\MiscellaneousExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMiscellaneousExpenses extends ListRecords
{
    protected static string $resource = MiscellaneousExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
