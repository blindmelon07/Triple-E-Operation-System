<?php

namespace App\Filament\Resources\Deliveries\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SaleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'sale';

    protected static ?string $title = 'Delivered Items';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('sale_items.product.name')
                    ->label('Product')
                    ->listWithLineBreaks(),
                TextColumn::make('sale_items.quantity')
                    ->label('Quantity')
                    ->listWithLineBreaks(),
                TextColumn::make('sale_items.price')
                    ->label('Price')
                    ->money('PHP')
                    ->listWithLineBreaks(),
            ]);
    }
}
