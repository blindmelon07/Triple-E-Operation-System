<?php

namespace App\Filament\Resources\Purchases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('date')->date(),
                \Filament\Tables\Columns\TextColumn::make('purchase_items_count')->counts('purchase_items')->label('Items'),
                \Filament\Tables\Columns\TextColumn::make('total')->money('USD'),
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
