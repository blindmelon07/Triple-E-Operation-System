<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vehicle Information')
                    ->schema([
                        TextInput::make('plate_number')
                            ->label('Plate Number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('ABC 1234'),

                        TextInput::make('make')
                            ->label('Make')
                            ->required()
                            ->maxLength(100)
                            ->datalist([
                                'Toyota', 'Honda', 'Mitsubishi', 'Nissan', 'Ford',
                                'Isuzu', 'Hyundai', 'Suzuki', 'Kia', 'Mazda',
                            ]),

                        TextInput::make('model')
                            ->label('Model')
                            ->required()
                            ->maxLength(100),

                        TextInput::make('year')
                            ->label('Year')
                            ->required()
                            ->numeric()
                            ->minValue(1990)
                            ->maxValue(date('Y') + 1),

                        TextInput::make('color')
                            ->label('Color')
                            ->maxLength(50),

                        Select::make('fuel_type')
                            ->label('Fuel Type')
                            ->options([
                                'gasoline' => 'Gasoline',
                                'diesel' => 'Diesel',
                                'electric' => 'Electric',
                                'hybrid' => 'Hybrid',
                            ])
                            ->default('gasoline')
                            ->required(),

                        Select::make('transmission')
                            ->label('Transmission')
                            ->options([
                                'automatic' => 'Automatic',
                                'manual' => 'Manual',
                            ])
                            ->default('automatic')
                            ->required(),
                    ])
                    ->columns(3),

                Section::make('Identification')
                    ->schema([
                        TextInput::make('vin')
                            ->label('VIN (Vehicle Identification Number)')
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        TextInput::make('engine_number')
                            ->label('Engine Number')
                            ->maxLength(50),

                        TextInput::make('current_mileage')
                            ->label('Current Mileage (km)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix('km'),
                    ])
                    ->columns(3),

                Section::make('Acquisition & Status')
                    ->schema([
                        DatePicker::make('acquisition_date')
                            ->label('Acquisition Date')
                            ->maxDate(now()),

                        TextInput::make('acquisition_cost')
                            ->label('Acquisition Cost')
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Active',
                                'maintenance' => 'Under Maintenance',
                                'inactive' => 'Inactive',
                                'sold' => 'Sold',
                            ])
                            ->default('active')
                            ->required(),

                        TextInput::make('assigned_driver')
                            ->label('Assigned Driver')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->collapsed(),
            ]);
    }
}
