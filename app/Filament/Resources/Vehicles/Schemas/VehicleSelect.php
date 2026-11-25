<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use App\Models\Vehicle;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * Shared "Truck/Unit" dropdown with a "+" button to add a new vehicle
 * without leaving the current form.
 */
class VehicleSelect
{
    public static function make(string $name = 'vehicle_id'): Select
    {
        return Select::make($name)
            ->label('Truck/Unit')
            // Closure so a newly created vehicle appears in the list immediately.
            ->options(fn () => Vehicle::whereIn('status', ['active', 'maintenance'])
                ->orderBy('make')
                ->get()
                ->mapWithKeys(fn (Vehicle $v) => [$v->id => $v->full_name]))
            ->searchable()
            ->preload()
            ->live()
            ->createOptionModalHeading('Add Truck/Unit')
            ->createOptionForm([
                Grid::make(2)->schema([
                    TextInput::make('plate_number')
                        ->label('Plate Number')
                        ->required()
                        ->unique(table: Vehicle::class, column: 'plate_number')
                        ->maxLength(20)
                        ->placeholder('ABC 1234'),

                    TextInput::make('make')
                        ->label('Make')
                        ->required()
                        ->maxLength(100)
                        ->datalist(['Isuzu', 'Mitsubishi', 'Toyota', 'Hino', 'Fuso', 'Nissan', 'Hyundai', 'Ford']),

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

                    Select::make('fuel_type')
                        ->label('Fuel Type')
                        ->options([
                            'gasoline' => 'Gasoline',
                            'diesel' => 'Diesel',
                            'electric' => 'Electric',
                            'hybrid' => 'Hybrid',
                        ])
                        ->default('diesel')
                        ->required(),

                    TextInput::make('current_mileage')
                        ->label('Current Mileage (km)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->suffix('km'),
                ]),
            ])
            ->createOptionUsing(fn (array $data): int => Vehicle::create([
                ...$data,
                'current_mileage' => $data['current_mileage'] ?? 0,
                'status' => 'active',
            ])->getKey())
            ->createOptionAction(fn (Action $action) => $action
                ->tooltip('Add new truck/unit')
                ->visible(fn () => auth()->user()?->can('create', Vehicle::class) ?? false));
    }
}
