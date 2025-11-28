<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')->required(),
                \Filament\Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                \Filament\Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Supplier')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('price')->numeric()->required(),
                \Filament\Forms\Components\TextInput::make('quantity')
                    ->label('Stock')
                    ->numeric()
                    ->required(),
            ]);
    }
}
