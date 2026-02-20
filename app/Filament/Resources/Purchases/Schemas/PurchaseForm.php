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
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if ($state) {
                            $supplier = \App\Models\Supplier::find($state);
                            if ($supplier && $supplier->payment_term_days > 0) {
                                $purchaseDate = $get('date') ?? now()->toDateString();
                                $set('due_date', \Carbon\Carbon::parse($purchaseDate)->addDays($supplier->payment_term_days)->toDateString());
                            } else {
                                $set('due_date', null);
                            }
                        }
                    }),
                \Filament\Forms\Components\DatePicker::make('date')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $supplierId = $get('supplier_id');
                        if ($supplierId && $state) {
                            $supplier = \App\Models\Supplier::find($supplierId);
                            if ($supplier && $supplier->payment_term_days > 0) {
                                $set('due_date', \Carbon\Carbon::parse($state)->addDays($supplier->payment_term_days)->toDateString());
                            }
                        }
                    }),
                \Filament\Schemas\Components\Section::make('Payment Terms')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->helperText('Auto-filled from supplier payment terms. You can override it.'),
                        \Filament\Forms\Components\Select::make('payment_status')
                            ->options([
                                'unpaid'  => 'Unpaid',
                                'partial' => 'Partial',
                                'paid'    => 'Paid',
                            ])
                            ->default('unpaid')
                            ->required()
                            ->reactive(),
                        \Filament\Forms\Components\TextInput::make('amount_paid')
                            ->numeric()
                            ->default(0)
                            ->prefix('₱')
                            ->visible(fn (callable $get) => in_array($get('payment_status'), ['partial', 'paid'])),
                        \Filament\Forms\Components\DatePicker::make('paid_date')
                            ->label('Paid Date')
                            ->visible(fn (callable $get) => $get('payment_status') === 'paid'),
                    ])
                    ->columns(2),
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
                                        $set('unit', $product->unit?->value);
                                    }
                                }
                            }),
                        \Filament\Forms\Components\Select::make('unit')
                            ->options(\App\Enums\ProductUnit::class)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('quantity')->label('Ordered Qty')->numeric()->required(),
                        \Filament\Forms\Components\TextInput::make('quantity_received')->label('Received Units')->numeric()->default(0)->minValue(0)->required(),
                        \Filament\Forms\Components\TextInput::make('price')->numeric()->required(),
                    ])
                    ->required(),
            ]);
    }
}
