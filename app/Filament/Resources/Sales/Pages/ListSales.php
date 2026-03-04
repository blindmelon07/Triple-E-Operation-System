<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Exports\SalesSummaryExport;
use App\Filament\Resources\Sales\SaleResource;
use App\Services\CsvExportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_summary')
                ->label('Download Summary')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->form([
                    Select::make('period')
                        ->label('Period')
                        ->options([
                            'today'      => 'Today',
                            'yesterday'  => 'Yesterday',
                            'this_week'  => 'This Week',
                            'last_week'  => 'Last Week',
                            'this_month' => 'This Month',
                            'last_month' => 'Last Month',
                            'this_year'  => 'This Year',
                            'custom'     => 'Custom Date Range',
                        ])
                        ->default('this_month')
                        ->live(),
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->visible(fn ($get) => $get('period') === 'custom')
                        ->required(fn ($get) => $get('period') === 'custom'),
                    DatePicker::make('date_until')
                        ->label('To Date')
                        ->visible(fn ($get) => $get('period') === 'custom')
                        ->required(fn ($get) => $get('period') === 'custom'),
                ])
                ->action(function (array $data) {
                    $export = new SalesSummaryExport(
                        period: $data['period'] !== 'custom' ? $data['period'] : null,
                        dateFrom: $data['date_from'] ?? null,
                        dateUntil: $data['date_until'] ?? null,
                    );

                    return (new CsvExportService)->export(
                        $export->getHeaders(),
                        $export->getData(),
                        $export->getFilename(),
                    );
                }),
            CreateAction::make(),
        ];
    }
}
