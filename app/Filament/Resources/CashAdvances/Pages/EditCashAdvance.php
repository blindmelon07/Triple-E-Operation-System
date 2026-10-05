<?php

namespace App\Filament\Resources\CashAdvances\Pages;

use App\Filament\Resources\CashAdvances\CashAdvanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCashAdvance extends EditRecord
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn () => ViewCashAdvance::hasPayrollDeductions($this->record)),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
