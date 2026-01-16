<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Enums\QuotationStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quotation Information')
                    ->schema([
                        TextInput::make('quotation_number')
                            ->label('Quotation Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated'),
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Customer'),
                        DatePicker::make('date')
                            ->required()
                            ->default(now())
                            ->label('Quotation Date'),
                        DatePicker::make('valid_until')
                            ->label('Valid Until')
                            ->minDate(now())
                            ->default(now()->addDays(30)),
                        Select::make('status')
                            ->options([
                                QuotationStatus::Pending->value => QuotationStatus::Pending->getLabel(),
                                QuotationStatus::Approved->value => QuotationStatus::Approved->getLabel(),
                                QuotationStatus::Rejected->value => QuotationStatus::Rejected->getLabel(),
                                QuotationStatus::ConvertedToSale->value => QuotationStatus::ConvertedToSale->getLabel(),
                                QuotationStatus::Expired->value => QuotationStatus::Expired->getLabel(),
                            ])
                            ->default(QuotationStatus::Pending->value)
                            ->required()
                            ->label('Status'),
                    ])
                    ->columns(2),

                Section::make('Quotation Items')
                    ->schema([
                        Repeater::make('quotation_items')
                            ->relationship()
                            ->schema([
                                Toggle::make('is_manual')
                                    ->label('Manual Entry')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('product_id', null);
                                        } else {
                                            $set('product_description', null);
                                        }
                                    })
                                    ->default(false)
                                    ->columnSpanFull(),
                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required(fn (callable $get) => !$get('is_manual'))
                                    ->reactive()
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (callable $get) => !$get('is_manual'))
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->price);
                                                $quantity = $get('quantity') ?: 1;
                                                $set('price', $product->price * $quantity);
                                            }
                                        }
                                    })
                                    ->label('Product'),
                                TextInput::make('product_description')
                                    ->label('Product Description')
                                    ->required(fn (callable $get) => $get('is_manual'))
                                    ->visible(fn (callable $get) => $get('is_manual'))
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $unitPrice = $get('unit_price');
                                        if ($unitPrice && $state) {
                                            $set('price', $unitPrice * $state);
                                        }
                                    })
                                    ->label('Quantity'),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₱')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        $quantity = $get('quantity');
                                        if ($quantity && $state) {
                                            $set('price', $state * $quantity);
                                        }
                                    })
                                    ->label('Unit Price'),
                                TextInput::make('price')
                                    ->numeric()
                                    ->required()
                                    ->prefix('₱')
                                    ->disabled()
                                    ->dehydrated()
                                    ->label('Total Price'),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->addActionLabel('Add Item')
                            ->reorderable(false)
                            ->collapsible()
                            ->cloneable(),
                    ]),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                        Placeholder::make('total')
                            ->label('Total Amount')
                            ->content(function ($get, $record) {
                                if ($record) {
                                    return '₱' . number_format($record->total, 2);
                                }
                                return '₱0.00';
                            }),
                    ])
                    ->columns(2),
            ]);
    }
}
