<?php

namespace App\Filament\Resources\MiscellaneousExpenses\Pages;

use App\Filament\Resources\MiscellaneousExpenses\MiscellaneousExpenseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateMiscellaneousExpense extends CreateRecord
{
    protected static string $resource = MiscellaneousExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }
}
