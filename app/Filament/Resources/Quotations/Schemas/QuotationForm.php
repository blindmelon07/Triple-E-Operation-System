<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Enums\ProductUnit;
use App\Enums\QuotationStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Header Section with Basic Information
                Section::make('Quotation Details')
                    ->description('Basic quotation information and customer details')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('quotation_number')
                                    ->label('Quotation Number')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated on save')
                                    ->helperText('Automatically generated in format: QT-YYYYMMDD-####')
                                    ->columnSpan(1),

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
                                    ->native(false)
                                    ->label('Status')
                                    ->helperText('Current status of this quotation')
                                    ->columnSpan(2),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Select::make('customer_id')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->native(false)
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('email')->email(),
                                        TextInput::make('phone')->tel(),
                                        Textarea::make('address'),
                                    ])
                                    ->label('Customer')
                                    ->helperText('Select an existing customer or create a new one')
                                    ->columnSpan(2),

                                Placeholder::make('customer_info')
                                    ->label('Customer Details')
                                    ->content(function ($get) {
                                        $customerId = $get('customer_id');
                                        if ($customerId) {
                                            $customer = \App\Models\Customer::find($customerId);
                                            if ($customer) {
                                                $info = [];
                                                if ($customer->email) $info[] = "Email: {$customer->email}";
                                                if ($customer->phone) $info[] = "Phone: {$customer->phone}";
                                                if ($customer->address) $info[] = "Address: {$customer->address}";
                                                return $info ? implode("\n", $info) : 'No additional details';
                                            }
                                        }
                                        return 'Select a customer to view details';
                                    })
                                    ->columnSpan(1),
                            ]),

                        Grid::make(3)
                            ->schema([
                                DatePicker::make('date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('M d, Y')
                                    ->label('Quotation Date')
                                    ->helperText('Date when this quotation is issued')
                                    ->columnSpan(1),

                                DatePicker::make('valid_until')
                                    ->label('Valid Until')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('M d, Y')
                                    ->minDate(now())
                                    ->default(now()->addDays(15))
                                    ->helperText('Expiration date for this quotation (default: 15 days)')
                                    ->columnSpan(1),

                                Placeholder::make('validity_days')
                                    ->label('Validity Period')
                                    ->content(function ($get) {
                                        $date = $get('date');
                                        $validUntil = $get('valid_until');
                                        if ($date && $validUntil) {
                                            $days = now()->parse($validUntil)->diffInDays(now()->parse($date));
                                            return $days . ' days';
                                        }
                                        return '15 days (default)';
                                    })
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // Items Section
                Section::make('Quotation Items')
                    ->description('Add products or services to this quotation')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Repeater::make('quotation_items')
                            ->relationship()
                            ->schema([
                                Toggle::make('is_manual')
                                    ->label('Custom Item')
                                    ->inline(false)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('product_id', null);
                                        } else {
                                            $set('product_description', null);
                                        }
                                    })
                                    ->default(false)
                                    ->helperText('Enable to enter a custom item not in the product list'),

                                Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required(fn (callable $get) => !$get('is_manual'))
                                    ->reactive()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->visible(fn (callable $get) => !$get('is_manual'))
                                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->price);
                                                $set('unit', $product->unit->value);
                                                $quantity = $get('quantity') ?: 1;
                                                $set('price', $product->price * $quantity);
                                            }
                                        }
                                    })
                                    ->label('Product / Service')
                                    ->helperText('Select from existing products'),

                                TextInput::make('product_description')
                                    ->label('Item Description')
                                    ->required(fn (callable $get) => $get('is_manual'))
                                    ->visible(fn (callable $get) => $get('is_manual'))
                                    ->maxLength(255)
                                    ->placeholder('Enter product or service name')
                                    ->helperText('Describe the custom item'),

                                Select::make('unit')
                                    ->label('Unit')
                                    ->options(collect(ProductUnit::cases())->mapWithKeys(fn($unit) => [$unit->value => $unit->label()]))
                                    ->required()
                                    ->native(false)
                                    ->searchable()
                                    ->helperText('Unit of measurement'),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('quantity')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(0.01)
                                            ->step(0.01)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                $unitPrice = $get('unit_price');
                                                if ($unitPrice && $state) {
                                                    $set('price', $unitPrice * $state);
                                                }
                                            })
                                            ->label('Quantity')
                                            ->columnSpan(1),

                                        TextInput::make('unit_price')
                                            ->numeric()
                                            ->required()
                                            ->prefix('₱')
                                            ->step(0.01)
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                                $quantity = $get('quantity');
                                                if ($quantity && $state) {
                                                    $set('price', $state * $quantity);
                                                }
                                            })
                                            ->label('Unit Price')
                                            ->columnSpan(1),

                                        TextInput::make('price')
                                            ->numeric()
                                            ->required()
                                            ->prefix('₱')
                                            ->disabled()
                                            ->dehydrated()
                                            ->label('Line Total')
                                            ->helperText('Auto-calculated')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Add Another Item')
                            ->reorderable(true)
                            ->collapsible()
                            ->collapsed(false)
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string =>
                                $state['product_description'] ??
                                (\App\Models\Product::find($state['product_id'] ?? null)?->name ?? 'New Item')
                            )
                            ->deleteAction(
                                fn ($action) => $action->requiresConfirmation()
                            ),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // Summary Section
                Section::make('Quotation Summary')
                    ->description('Review totals and add additional notes')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Textarea::make('notes')
                                    ->label('Terms & Conditions / Notes')
                                    ->rows(5)
                                    ->placeholder('Enter any special terms, conditions, or additional information...')
                                    ->helperText('This will appear on the quotation document')
                                    ->columnSpan(1),

                                Grid::make(1)
                                    ->schema([
                                        Placeholder::make('items_summary')
                                            ->label('Items Summary')
                                            ->content(function ($get, $record) {
                                                if ($record && $record->quotation_items) {
                                                    $itemCount = $record->quotation_items->count();
                                                    return $itemCount . ' ' . str('item')->plural($itemCount);
                                                }
                                                return 'Add items above to see summary';
                                            }),

                                        Placeholder::make('subtotal')
                                            ->label('Subtotal')
                                            ->content(function ($get, $record) {
                                                if ($record) {
                                                    return '₱' . number_format($record->total, 2);
                                                }
                                                return '₱0.00';
                                            }),

                                        Placeholder::make('total')
                                            ->label('Total Amount')
                                            ->content(function ($get, $record) {
                                                if ($record) {
                                                    return '₱' . number_format($record->total, 2);
                                                }
                                                return '₱0.00';
                                            })
                                            ->extraAttributes(['class' => 'text-2xl font-bold']),
                                    ])
                                    ->columnSpan(1),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }
}
