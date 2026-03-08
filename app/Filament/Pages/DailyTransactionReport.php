<?php

namespace App\Filament\Pages;

use App\Models\CashRegisterSession;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DailyTransactionReport extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected string $view = 'filament.pages.daily-transaction-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Daily Transaction Report';

    protected static ?string $title = 'Daily Transaction Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_period_report')
                ->label('Generate Period Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
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
                            'custom'     => 'Custom Range',
                        ])
                        ->required()
                        ->live(),
                    DatePicker::make('date_from')
                        ->label('From')
                        ->visible(fn ($get) => $get('period') === 'custom')
                        ->required(fn ($get) => $get('period') === 'custom'),
                    DatePicker::make('date_to')
                        ->label('Until')
                        ->visible(fn ($get) => $get('period') === 'custom')
                        ->required(fn ($get) => $get('period') === 'custom'),
                ])
                ->action(function (array $data) {
                    [$from, $to] = $this->resolveDateRange($data);
                    $url = route('pos.reports.period', ['date_from' => $from, 'date_to' => $to]);
                    $this->js("window.open('{$url}', '_blank')");
                }),
        ];
    }

    private function resolveDateRange(array $data): array
    {
        return match ($data['period']) {
            'today'      => [today()->toDateString(), today()->toDateString()],
            'yesterday'  => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'this_week'  => [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            'last_week'  => [now()->subWeek()->startOfWeek()->toDateString(), now()->subWeek()->endOfWeek()->toDateString()],
            'this_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'last_month' => [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()],
            'custom'     => [$data['date_from'], $data['date_to']],
            default      => [today()->toDateString(), today()->toDateString()],
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CashRegisterSession::query()
                    ->with('user')
                    ->where('status', 'Closed')
                    ->latest('closed_at')
            )
            ->columns([
                TextColumn::make('opened_at')
                    ->label('Date')
                    ->date('F d, Y')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Cashier'),
                TextColumn::make('opening_amount')
                    ->label('Petty Cash')
                    ->money('PHP'),
                TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->money('PHP'),
                TextColumn::make('total_transactions')
                    ->label('Transactions'),
                TextColumn::make('closing_amount')
                    ->label('Cash on Hand')
                    ->money('PHP'),
                TextColumn::make('discrepancy')
                    ->label('Short/Over')
                    ->money('PHP')
                    ->color(fn ($record) => (float)$record->discrepancy < 0 ? 'danger' : ((float)$record->discrepancy > 0 ? 'success' : 'gray')),
                TextColumn::make('closed_at')
                    ->label('Closed At')
                    ->dateTime('h:i A')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('period')
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
                                'custom'     => 'Custom Range',
                            ])
                            ->placeholder('All Time')
                            ->live(),
                        DatePicker::make('date_from')
                            ->label('From')
                            ->visible(fn ($get) => $get('period') === 'custom'),
                        DatePicker::make('date_until')
                            ->label('Until')
                            ->visible(fn ($get) => $get('period') === 'custom'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $period = $data['period'] ?? null;

                        return match ($period) {
                            'today'      => $query->whereDate('opened_at', today()),
                            'yesterday'  => $query->whereDate('opened_at', today()->subDay()),
                            'this_week'  => $query->whereBetween('opened_at', [now()->startOfWeek(), now()->endOfWeek()]),
                            'last_week'  => $query->whereBetween('opened_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
                            'this_month' => $query->whereMonth('opened_at', now()->month)->whereYear('opened_at', now()->year),
                            'last_month' => $query->whereMonth('opened_at', now()->subMonth()->month)->whereYear('opened_at', now()->subMonth()->year),
                            'custom'     => $query
                                ->when($data['date_from'],  fn ($q, $d) => $q->whereDate('opened_at', '>=', $d))
                                ->when($data['date_until'], fn ($q, $d) => $q->whereDate('opened_at', '<=', $d)),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        return match ($data['period'] ?? null) {
                            'today'      => 'Today',
                            'yesterday'  => 'Yesterday',
                            'this_week'  => 'This Week',
                            'last_week'  => 'Last Week',
                            'this_month' => 'This Month',
                            'last_month' => 'Last Month',
                            'custom'     => 'Custom: ' . ($data['date_from'] ?? '?') . ' → ' . ($data['date_until'] ?? '?'),
                            default      => null,
                        };
                    }),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(fn (CashRegisterSession $record) => route('pos.register.daily-report', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('opened_at', 'desc')
            ->striped();
    }
}
