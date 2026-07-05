<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductUnit;
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
                \Filament\Forms\Components\Select::make('unit')
                    ->options(ProductUnit::class)
                    ->default(ProductUnit::Piece)
                    ->required()
                    ->reactive(),
                \Filament\Forms\Components\Repeater::make('unitPrices')
                    ->relationship()
                    ->label('Additional Sellable Units')
                    ->helperText('Let this product be sold in another unit at its own price, e.g. sell a hose per Meter but also per Roll.')
                    ->schema([
                        \Filament\Forms\Components\Select::make('unit')
                            ->options(fn (callable $get) => collect(ProductUnit::cases())
                                ->reject(fn (ProductUnit $case) => $case->value === $get('../../unit'))
                                ->mapWithKeys(fn (ProductUnit $case) => [$case->value => $case->label()]))
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('conversion_factor')
                            ->label('Equivalent Base Units')
                            ->helperText('How many of the base unit above equal 1 of this unit, e.g. 50 if 1 Roll = 50 Meters.')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(3)
                    ->distinct()
                    ->defaultItems(0),
            ]);
    }
}
