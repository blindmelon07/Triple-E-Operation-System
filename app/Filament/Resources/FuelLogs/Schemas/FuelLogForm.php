<?php

namespace App\Filament\Resources\FuelLogs\Schemas;

use App\Models\FuelLog;
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

class FuelLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fuel Information')
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->default(fn () => FuelLog::generateReferenceNumber())
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
                                        $set('odometer_reading', $vehicle->current_mileage);
                                    }
                                }
                            }),

                        DatePicker::make('fuel_date')
                            ->label('Fuel Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        TextInput::make('odometer_reading')
                            ->label('Odometer Reading')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('km'),

                        TextInput::make('fuel_station')
                            ->label('Fuel Station')
                            ->maxLength(255)
                            ->placeholder('Gas station name'),
                    ])
                    ->columns(2),

                Section::make('Cost Breakdown')
                    ->schema([
                        TextInput::make('liters')
                            ->label('Liters')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix('L')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $liters = floatval($get('liters') ?? 0);
                                $price = floatval($get('price_per_liter') ?? 0);
                                $set('cost', round($liters * $price, 2));
                            }),

                        TextInput::make('price_per_liter')
                            ->label('Price per Liter')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $liters = floatval($get('liters') ?? 0);
                                $price = floatval($get('price_per_liter') ?? 0);
                                $set('cost', round($liters * $price, 2));
                            }),

                        TextInput::make('cost')
                            ->label('Total Cost')
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Section::make('Additional Details')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000),

                        FileUpload::make('receipt_path')
                            ->label('Receipt')
                            ->directory('fuel-receipts')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/*', 'application/pdf']),
                    ])
                    ->columns(1)
                    ->collapsed(),
            ]);
    }
}
