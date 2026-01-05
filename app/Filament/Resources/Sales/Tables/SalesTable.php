<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('customer.name')->label('Customer')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('date')->date(),
                \Filament\Tables\Columns\TextColumn::make('sale_items_count')->counts('sale_items')->label('Items'),
                \Filament\Tables\Columns\TextColumn::make('total')->money('Php')->summarize(Sum::make()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
