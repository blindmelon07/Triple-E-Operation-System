<?php

namespace App\Filament\Resources\MiscellaneousExpenses\Pages;

use App\Filament\Resources\MiscellaneousExpenses\MiscellaneousExpenseResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMiscellaneousExpense extends EditRecord
{
    protected static string $resource = MiscellaneousExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
