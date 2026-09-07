<?php

namespace App\Filament\Resources\FuelLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FuelLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('fuel_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('vehicle.plate_number')
                    ->label('Vehicle')
                    ->description(fn ($record) => $record->vehicle?->full_name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('liters')
                    ->label('Liters')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' L')
                    ->sortable(),

                TextColumn::make('price_per_liter')
                    ->label('Price/L')
                    ->money('PHP')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('cost')
                    ->label('Total Cost')
                    ->money('PHP')
                    ->sortable()
                    ->summarize([
                        Sum::make()->money('PHP'),
                    ]),

                TextColumn::make('odometer_reading')
                    ->label('Odometer')
                    ->numeric()
                    ->suffix(' km')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fuel_station')
                    ->label('Station')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user.name')
                    ->label('Recorded By')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('vehicle_id')
                    ->label('Vehicle')
                    ->relationship('vehicle', 'plate_number')
                    ->searchable()
                    ->preload(),

                Filter::make('fuel_date')
                    ->form([
                        DatePicker::make('from')
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fuel_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fuel_date', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fuel_date', 'desc');
    }
}
