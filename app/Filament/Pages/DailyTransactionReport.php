<?php

namespace App\Filament\Pages;

use App\Models\CashRegisterSession;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use UnitEnum;
use BackedEnum;

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
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')->label('From'),
                        DatePicker::make('date_until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'],
                                fn (Builder $q, $d) => $q->whereDate('opened_at', '>=', $d))
                            ->when($data['date_until'],
                                fn (Builder $q, $d) => $q->whereDate('opened_at', '<=', $d));
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
