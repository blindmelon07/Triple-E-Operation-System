<?php

namespace App\Filament\Resources\CashAdvances\Pages;

use App\Filament\Resources\CashAdvances\CashAdvanceResource;
use App\Models\CashAdvance;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCashAdvance extends ViewRecord
{
    protected static string $resource = CashAdvanceResource::class;

    /**
     * Deleting an advance would cascade away payment rows that a payroll
     * already deducted from someone's pay — block it once that has happened.
     */
    public static function hasPayrollDeductions(CashAdvance $record): bool
    {
        return $record->payments()->effective()->whereNotNull('payroll_item_id')->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make()
                ->hidden(fn () => static::hasPayrollDeductions($this->record)),
        ];
    }
}
