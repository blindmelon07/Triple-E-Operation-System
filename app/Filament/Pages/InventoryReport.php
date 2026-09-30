<?php

namespace App\Filament\Pages;

use App\Exports\InventoryReportExport;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\CsvExportService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use UnitEnum;

class InventoryReport extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;
    protected string $view = 'filament.pages.inventory-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Inventory In/Out';

    protected static ?string $title = 'Inventory In/Out Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    /**
     * 'summary' = one row per product with total in/out, 'detailed' = every movement.
     */
    public string $viewMode = 'summary';

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['summary', 'detailed'], true) || $mode === $this->viewMode) {
            return;
        }

        $this->viewMode = $mode;

        // Sort columns differ between modes, so drop the current sort but keep the filters.
        $this->tableSort = null;
        $this->bootedInteractsWithTable();
        $this->resetPage();
        $this->flushCachedTableRecords();
    }

    protected function isSummary(): bool
    {
        return $this->viewMode === 'summary';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export to CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Select::make('format')
                        ->label('Format')
                        ->options([
                            'summary' => 'Summary per Product',
                            'detailed' => 'Detailed (every movement)',
                        ])
                        ->default(fn () => $this->viewMode)
                        ->required(),
                    Select::make('type')
                        ->label('Type')
                        ->options([
                            'in' => 'In',
                            'out' => 'Out',
                        ])
                        ->default(fn () => $this->tableFilters['type']['value'] ?? null)
                        ->placeholder('All (In & Out)'),
                    Select::make('period')
                        ->label('Period')
                        ->options([
                            ...InventoryReportExport::periodOptions(),
                            'custom' => 'Custom Date Range',
                        ])
                        ->default(fn () => $this->tableFilters['period']['value'] ?? 'this_month')
                        ->required()
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
                    $export = new InventoryReportExport(
                        period: $data['period'] !== 'custom' ? $data['period'] : null,
                        dateFrom: $data['date_from'] ?? null,
                        dateUntil: $data['date_until'] ?? null,
                        type: $data['type'] ?? null,
                        summary: ($data['format'] ?? 'detailed') === 'summary',
                    );

                    return (new CsvExportService)->export(
                        $export->getHeaders(),
                        $export->getData(),
                        $export->getFilename(),
                    );
                }),
        ];
    }

    /**
     * One row per product, with in/out totals computed from the movements that
     * match the currently applied filters.
     */
    protected function getSummaryQuery(): Builder
    {
        $filters = $this->tableFilters ?? [];
        $dateFrom = $filters['date_range']['date_from'] ?? null;
        $dateUntil = $filters['date_range']['date_until'] ?? null;
        $type = $filters['type']['value'] ?? null;
        $period = $filters['period']['value'] ?? null;

        $inRange = fn (Builder $query): Builder => InventoryReportExport::applyPeriod($query, 'created_at', $period)
            ->when($dateFrom, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
            ->when($dateUntil, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date));

        $matchesFilters = fn (Builder $query): Builder => $inRange($query)
            ->when($type, fn (Builder $query, string $type): Builder => $query->where('type', $type));

        return Product::query()
            ->with('inventory')
            ->withSum(['inventoryMovements as total_in' => fn (Builder $query) => $inRange($query)->where('type', 'in')], 'quantity')
            ->withSum(['inventoryMovements as total_out' => fn (Builder $query) => $inRange($query)->where('type', 'out')], 'quantity')
            ->withCount(['inventoryMovements as movements_count' => $matchesFilters])
            ->whereHas('inventoryMovements', $matchesFilters);
    }

    public function table(Table $table): Table
    {
        $detailed = fn (): bool => ! $this->isSummary();
        $summary = fn (): bool => $this->isSummary();

        return $table
            ->query(fn (): Builder => $this->isSummary()
                ? $this->getSummaryQuery()
                : InventoryMovement::query()->with(['product']))
            ->columns([
                // Detailed view
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->visible($detailed),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->visible($detailed),
                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->visible($detailed),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->label('Total In')
                            ->numeric()
                            ->query(fn (QueryBuilder $query): QueryBuilder => $query->where('type', 'in')),
                        Sum::make()
                            ->label('Total Out')
                            ->numeric()
                            ->query(fn (QueryBuilder $query): QueryBuilder => $query->where('type', 'out')),
                    ])
                    ->visible($detailed),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->sortable()
                    ->visible($detailed),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->visible($detailed),

                // Summary view
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->visible($summary),
                TextColumn::make('total_in')
                    ->label('Total In')
                    ->numeric()
                    ->default(0)
                    ->color('success')
                    ->weight('bold')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total_in', $direction))
                    ->summarize(Sum::make()->label('Total In')->numeric())
                    ->visible($summary),
                TextColumn::make('total_out')
                    ->label('Total Out')
                    ->numeric()
                    ->default(0)
                    ->color('danger')
                    ->weight('bold')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total_out', $direction))
                    ->summarize(Sum::make()->label('Total Out')->numeric())
                    ->visible($summary),
                TextColumn::make('net')
                    ->label('Net (In - Out)')
                    ->state(fn (Product $record): float => (float) $record->total_in - (float) $record->total_out)
                    ->numeric()
                    ->color(fn ($state): string => $state < 0 ? 'danger' : ($state > 0 ? 'success' : 'gray'))
                    ->summarize(
                        Summarizer::make()
                            ->label('Total Net')
                            ->numeric()
                            ->using(fn (QueryBuilder $query): float => (float) $query->sum('total_in') - (float) $query->sum('total_out')),
                    )
                    ->visible($summary),
                TextColumn::make('movements_count')
                    ->label('No. of Movements')
                    ->numeric()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('movements_count', $direction))
                    ->summarize(Sum::make()->label('Total')->numeric())
                    ->visible($summary),
                TextColumn::make('inventory.quantity')
                    ->label('Current Stock')
                    ->numeric()
                    ->default(0)
                    ->visible($summary),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filters([
                SelectFilter::make('period')
                    ->label('Period')
                    ->placeholder('All Time')
                    ->options(InventoryReportExport::periodOptions())
                    // In summary mode the period is applied inside getSummaryQuery().
                    ->query(fn (Builder $query, array $data): Builder => $this->isSummary()
                        ? $query
                        : InventoryReportExport::applyPeriod($query, 'created_at', $data['value'] ?? null)),
                SelectFilter::make('type')
                    ->label('Type')
                    ->placeholder('All (In & Out)')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ])
                    // In summary mode the filter is applied inside getSummaryQuery().
                    ->query(fn (Builder $query, array $data): Builder => $this->isSummary()
                        ? $query
                        : $query->when($data['value'] ?? null, fn (Builder $query, $type): Builder => $query->where('type', $type))),
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From'),
                        DatePicker::make('date_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // In summary mode the date range is applied inside getSummaryQuery().
                        if ($this->isSummary()) {
                            return $query;
                        }

                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort(
                fn (): string => $this->isSummary() ? 'name' : 'created_at',
                fn (): string => $this->isSummary() ? 'asc' : 'desc',
            )
            ->paginated([10, 25, 50])
            ->striped();
    }
}
