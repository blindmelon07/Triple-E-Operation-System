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
                \Filament\Forms\Components\TextInput::make('si_number')
                    ->label('SI #')
                    ->helperText('Supplier invoice number.'),
                \Filament\Forms\Components\TextInput::make('po_number')
                    ->label('P.O #')
                    ->helperText('Purchase order number.'),
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
                        // UI-only switch: a custom item is a line that isn't in
                        // the product catalog (one-off part, service, fee). It
                        // has a free-text name and never touches inventory.
                        \Filament\Forms\Components\Toggle::make('is_custom')
                            ->label('Custom item')
                            ->helperText('Not in the product list. Won\'t affect inventory.')
                            ->dehydrated(false)
                            ->reactive()
                            ->afterStateHydrated(fn ($component, callable $get) => $component->state(filled($get('custom_item_name'))))
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('product_id', null);
                                } else {
                                    $set('custom_item_name', null);
                                }
                            })
                            ->columnSpanFull(),
                        \Filament\Forms\Components\TextInput::make('custom_item_name')
                            ->label('Item Name')
                            ->maxLength(255)
                            ->required(fn (callable $get) => (bool) $get('is_custom'))
                            ->visible(fn (callable $get) => (bool) $get('is_custom')),
                        \Filament\Forms\Components\Select::make('product_id')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(fn (callable $get) => ! $get('is_custom'))
                            ->visible(fn (callable $get) => ! $get('is_custom'))
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
                        \Filament\Forms\Components\Placeholder::make('supplier_price_comparison')
                            ->label('Base Price by Supplier')
                            ->content(function (callable $get) {
                                $productId = $get('product_id');

                                if (! $productId) {
                                    return 'Select a product to compare supplier base prices.';
                                }

                                $prices = \App\Models\SupplierProductPrice::with('supplier')
                                    ->where('product_id', $productId)
                                    ->get()
                                    ->sortBy('base_price');

                                if ($prices->isEmpty()) {
                                    return 'No supplier base prices recorded yet for this product.';
                                }

                                $currentSupplierId = $get('../../supplier_id');

                                $lines = $prices->map(function ($row) use ($currentSupplierId) {
                                    $line = e($row->supplier->name).': ₱'.number_format((float) $row->base_price, 2);

                                    return $row->supplier_id == $currentSupplierId
                                        ? "<strong>{$line} (selected supplier)</strong>"
                                        : $line;
                                })->implode('<br>');

                                return new \Illuminate\Support\HtmlString($lines);
                            })
                            ->visible(fn (callable $get) => ! $get('is_custom'))
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Select::make('unit')
                            ->options(\App\Enums\ProductUnit::class)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('quantity')->label('Ordered Qty')->numeric()->required(),
                        \Filament\Forms\Components\TextInput::make('quantity_received')->label('Received Units')->numeric()->default(0)->minValue(0)->required(),
                        \Filament\Forms\Components\TextInput::make('price')->numeric()->required(),
                    ])
                    ->itemLabel(fn (array $state): ?string => filled($state['custom_item_name'] ?? null)
                        ? $state['custom_item_name'].' (custom)'
                        : \App\Models\Product::find($state['product_id'] ?? null)?->name)
                    // Hidden fields aren't saved, so whichever of product_id /
                    // custom_item_name is hidden must be cleared explicitly —
                    // otherwise switching an existing line between product and
                    // custom would keep the stale value.
                    ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => self::normalizeItem($data))
                    ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => self::normalizeItem($data))
                    ->required(),
            ]);
    }

    private static function normalizeItem(array $data): array
    {
        if (filled($data['custom_item_name'] ?? null)) {
            $data['product_id'] = null;
        } else {
            $data['custom_item_name'] = null;
        }

        return $data;
    }
}
