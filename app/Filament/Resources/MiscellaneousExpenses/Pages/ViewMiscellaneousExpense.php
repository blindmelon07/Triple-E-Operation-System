<?php

namespace App\Filament\Resources\MiscellaneousExpenses\Pages;

use App\Filament\Resources\MiscellaneousExpenses\MiscellaneousExpenseResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMiscellaneousExpense extends ViewRecord
{
    protected static string $resource = MiscellaneousExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
