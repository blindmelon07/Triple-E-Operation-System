<?php

namespace App\Filament\Resources\MaintenanceRecords\Schemas;

use App\Models\MaintenanceRecord;
use App\Models\MaintenanceType;
use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MaintenanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maintenance Expense')
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->default(fn () => MaintenanceRecord::generateReferenceNumber())
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('maintenance_date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()->addMonth()),

                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required()->maxLength(255),
                            ]),

                        TextInput::make('si_number')
                            ->label('SI #')
                            ->helperText('Supplier invoice number.')
                            ->maxLength(255),

                        TextInput::make('po_number')
                            ->label('PO #')
                            ->helperText('Purchase order number.')
                            ->maxLength(255),

                        Select::make('maintenance_type_id')
                            ->label('Service Type')
                            ->relationship('maintenanceType', 'name')
                            ->options(MaintenanceType::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2),
                                TextInput::make('recommended_interval_km')
                                    ->numeric()
                                    ->suffix('km'),
                                TextInput::make('recommended_interval_months')
                                    ->numeric()
                                    ->suffix('months'),
                            ]),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('completed')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Product / Item')
                    ->schema([
                        Repeater::make('items')
                            ->label('')
                            ->relationship()
                            ->addActionLabel('+ Add Product Item')
                            ->schema([
                                TextInput::make('item_name')
                                    ->label('Product/Item')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Engine oil, brake pads')
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->label('Qty')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0)
                                    ->live(onBlur: true),

                                TextInput::make('unit_price')
                                    ->label('Price')
                                    ->numeric()
                                    ->required()
                                    ->default(0)
                                    ->prefix('₱')
                                    ->minValue(0)
                                    ->live(onBlur: true),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $items = $get('items') ?? [];
                                $total = collect($items)->sum(
                                    fn ($item) => floatval($item['quantity'] ?? 0) * floatval($item['unit_price'] ?? 0)
                                );
                                $set('cost', round($total, 2));
                            }),

                        TextInput::make('cost')
                            ->label('Total Amount')
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(1),

                Section::make('Truck / Unit')
                    ->schema([
                        Select::make('vehicle_id')
                            ->label('Truck/Unit')
                            ->options(
                                Vehicle::whereIn('status', ['active', 'maintenance'])
                                    ->get()
                                    ->mapWithKeys(fn ($v) => [$v->id => $v->full_name])
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?int $state) {
                                $vehicle = $state ? Vehicle::find($state) : null;
                                $set('plate_number_display', $vehicle?->plate_number);
                                $set('mileage_at_service', $vehicle?->current_mileage);
                            }),

                        TextInput::make('plate_number_display')
                            ->label('Plate Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->default(fn (Get $get) => Vehicle::find($get('vehicle_id'))?->plate_number),

                        TextInput::make('mileage_at_service')
                            ->label('Mileage at Service')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('km'),

                        TextInput::make('service_provider')
                            ->label('Service Provider / Shop')
                            ->maxLength(255)
                            ->placeholder('Auto shop or mechanic name'),
                    ])
                    ->columns(2),

                Section::make('Service Details')
                    ->schema([
                        Textarea::make('description')
                            ->label('Service Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Describe the service performed...'),

                        Textarea::make('parts_replaced')
                            ->label('Parts Replaced')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('List any parts that were replaced...'),

                        FileUpload::make('invoice_path')
                            ->label('Invoice / Receipt')
                            ->directory('maintenance-invoices')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/*', 'application/pdf']),
                    ])
                    ->columns(1)
                    ->collapsed(),

                Section::make('Next Service Reminder')
                    ->schema([
                        DatePicker::make('next_service_date')
                            ->label('Next Service Date')
                            ->minDate(now())
                            ->helperText('When should this service be done again?'),

                        TextInput::make('next_service_mileage')
                            ->label('Next Service Mileage')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('km')
                            ->helperText('At what mileage should this service be done again?'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
