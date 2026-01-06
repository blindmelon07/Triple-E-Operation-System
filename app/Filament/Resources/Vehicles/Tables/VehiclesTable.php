<?php

namespace App\Filament\Resources\Vehicles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plate_number')
                    ->label('Plate No.')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('full_name')
                    ->label('Vehicle')
                    ->searchable(['make', 'model'])
                    ->sortable(['make', 'model']),

                TextColumn::make('color')
                    ->label('Color')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('fuel_type')
                    ->label('Fuel')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'gasoline' => 'success',
                        'diesel' => 'warning',
                        'electric' => 'info',
                        'hybrid' => 'primary',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('current_mileage')
                    ->label('Mileage')
                    ->numeric()
                    ->suffix(' km')
                    ->sortable(),

                TextColumn::make('assigned_driver')
                    ->label('Driver')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'maintenance' => 'warning',
                        'inactive' => 'gray',
                        'sold' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Active',
                        'maintenance' => 'Maintenance',
                        'inactive' => 'Inactive',
                        'sold' => 'Sold',
                        default => $state,
                    }),

                TextColumn::make('maintenance_records_count')
                    ->label('Services')
                    ->counts('maintenanceRecords')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('acquisition_date')
                    ->label('Acquired')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'maintenance' => 'Under Maintenance',
                        'inactive' => 'Inactive',
                        'sold' => 'Sold',
                    ]),

                SelectFilter::make('fuel_type')
                    ->label('Fuel Type')
                    ->options([
                        'gasoline' => 'Gasoline',
                        'diesel' => 'Diesel',
                        'electric' => 'Electric',
                        'hybrid' => 'Hybrid',
                    ]),

                SelectFilter::make('transmission')
                    ->label('Transmission')
                    ->options([
                        'automatic' => 'Automatic',
                        'manual' => 'Manual',
                    ]),
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
            ->defaultSort('plate_number');
    }
}
