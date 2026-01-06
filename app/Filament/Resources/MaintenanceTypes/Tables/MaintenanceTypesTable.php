<?php

namespace App\Filament\Resources\MaintenanceTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MaintenanceTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Service Type')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('recommended_interval_km')
                    ->label('Interval (km)')
                    ->numeric()
                    ->suffix(' km')
                    ->placeholder('N/A')
                    ->sortable(),

                TextColumn::make('recommended_interval_months')
                    ->label('Interval (months)')
                    ->numeric()
                    ->suffix(' months')
                    ->placeholder('N/A')
                    ->sortable(),

                TextColumn::make('maintenance_records_count')
                    ->label('Records')
                    ->counts('maintenanceRecords')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Types')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
