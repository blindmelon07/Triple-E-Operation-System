<?php

namespace App\Filament\Resources\CashAdvances\Pages;

use App\Filament\Pages\CashAdvanceReport;
use App\Filament\Resources\CashAdvances\CashAdvanceResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCashAdvances extends ListRecords
{
    protected static string $resource = CashAdvanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('monitoringSheet')
                ->label('Monitoring Sheet')
                ->icon(Heroicon::OutlinedDocumentChartBar)
                ->color('gray')
                ->visible(fn () => CashAdvanceReport::canAccess())
                ->url(fn () => CashAdvanceReport::getUrl()),

            CreateAction::make(),
        ];
    }
}
