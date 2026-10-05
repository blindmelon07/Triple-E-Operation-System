<?php

namespace App\Filament\Resources\CashAdvances\Pages;

use App\Filament\Resources\CashAdvances\CashAdvanceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCashAdvance extends CreateRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
