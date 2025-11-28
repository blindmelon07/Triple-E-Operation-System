<?php

namespace App\Filament\Resources\Purchases\Schemas;

use Filament\Schemas\Schema;

class PurchaseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->required(),
                \Filament\Forms\Components\DatePicker::make('date')->required(),
                \Filament\Forms\Components\Repeater::make('purchase_items')
                    ->relationship()
                    ->schema([
                        \Filament\Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $product = \App\Models\Product::find($state);
                                    if ($product) {
                                        $set('price', $product->price);
                                    }
                                }
                            }),
                        \Filament\Forms\Components\TextInput::make('quantity')->numeric()->required(),
                        \Filament\Forms\Components\TextInput::make('price')->numeric()->required(),
                    ])
                    ->required(),
            ]);
    }
}
