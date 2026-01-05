<?php

namespace App\Filament\Pages;

use App\Exports\InventoryReportExport;
use App\Models\InventoryMovement;
use App\Services\CsvExportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InventoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.inventory-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Inventory In/Out';

    protected static ?string $title = 'Inventory In/Out Report';

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
                    $export = new InventoryReportExport(
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
            ->query(InventoryMovement::query()->with(['product']))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Reason')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50),
            ])
            ->filters([
                Filter::make('type')
                    ->form([
                        Select::make('type')
                            ->options([
                                'in' => 'In',
                                'out' => 'Out',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['type'],
                            fn (Builder $query, $type): Builder => $query->where('type', $type),
                        );
                    }),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->striped();
    }
}
