<?php

namespace App\Filament\Pages;

use App\Exports\SalesReportExport;
use App\Models\Sale;
use App\Services\CsvExportService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class SalesReport extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;
    protected string $view = 'filament.pages.sales-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Sales Report';

    protected static ?string $title = 'Sales Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export to CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Select::make('period')
                        ->label('Period')
                        ->options([
                            'today' => 'Today',
                            'yesterday' => 'Yesterday',
                            'this_week' => 'This Week',
                            'last_week' => 'Last Week',
                            'this_month' => 'This Month',
                            'last_month' => 'Last Month',
                            'this_year' => 'This Year',
                            'custom' => 'Custom Date Range',
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
                    $export = new SalesReportExport(
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
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query()->with(['customer', 'sale_items']))
            ->columns([
                TextColumn::make('date')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->default('Walk-in'),
                TextColumn::make('sale_items_count')
                    ->label('Items')
                    ->counts('sale_items'),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('PHP')
                    ->sortable()
                    ->summarize(Sum::make()->money('PHP')),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From'),
                        DatePicker::make('date_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50])
            ->striped();
    }
}
