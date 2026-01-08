<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('category.name')->label('Category')->searchable(),
                TextColumn::make('price')->money('PHP')->label('Price'),
                TextColumn::make('inventory.quantity')
                    ->label('Stock')
                    ->default(0)
                    ->numeric(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->formatStateUsing(fn ($state) => $state?->label()),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
