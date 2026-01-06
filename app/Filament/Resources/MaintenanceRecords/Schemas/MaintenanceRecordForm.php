<?php

namespace App\Filament\Resources\MaintenanceRecords\Schemas;

use App\Models\MaintenanceRecord;
use App\Models\MaintenanceType;
use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
                Section::make('Service Information')
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->default(fn () => MaintenanceRecord::generateReferenceNumber())
                            ->disabled()
                            ->dehydrated(),

                        Select::make('vehicle_id')
                            ->label('Vehicle')
                            ->options(
                                Vehicle::whereIn('status', ['active', 'maintenance'])
                                    ->get()
                                    ->mapWithKeys(fn ($v) => [$v->id => "{$v->plate_number} - {$v->full_name}"])
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?int $state) {
                                if ($state) {
                                    $vehicle = Vehicle::find($state);
                                    if ($vehicle) {
                                        $set('mileage_at_service', $vehicle->current_mileage);
                                    }
                                }
                            }),

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

                        DatePicker::make('maintenance_date')
                            ->label('Service Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()->addMonth()),

                        TextInput::make('mileage_at_service')
                            ->label('Mileage at Service')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('km'),

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
                    ->columns(2),

                Section::make('Cost Breakdown')
                    ->schema([
                        TextInput::make('parts_cost')
                            ->label('Parts Cost')
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $parts = floatval($get('parts_cost') ?? 0);
                                $labor = floatval($get('labor_cost') ?? 0);
                                $set('cost', $parts + $labor);
                            }),

                        TextInput::make('labor_cost')
                            ->label('Labor Cost')
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $parts = floatval($get('parts_cost') ?? 0);
                                $labor = floatval($get('labor_cost') ?? 0);
                                $set('cost', $parts + $labor);
                            }),

                        TextInput::make('cost')
                            ->label('Total Cost')
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),

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
                    ->columns(1),

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
