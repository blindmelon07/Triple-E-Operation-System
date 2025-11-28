<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->required(),
                \Filament\Forms\Components\DatePicker::make('date')->required(),
                \Filament\Forms\Components\Repeater::make('sale_items')
                    ->relationship()
                    ->schema([
                        \Filament\Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('quantity')->numeric()->required(),
                        \Filament\Forms\Components\TextInput::make('price')->numeric()->required(),
                    ])
                    ->required(),
            ]);
    }
}
