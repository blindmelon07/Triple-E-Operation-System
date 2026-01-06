<?php

namespace App\Filament\Resources\MaintenanceTypes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MaintenanceTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Service Type Name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->placeholder('e.g., Oil Change, Tire Rotation'),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(500),

                TextInput::make('recommended_interval_km')
                    ->label('Recommended Interval (km)')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('km')
                    ->helperText('Leave empty if not applicable'),

                TextInput::make('recommended_interval_months')
                    ->label('Recommended Interval (months)')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('months')
                    ->helperText('Leave empty if not applicable'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive types will not appear in maintenance forms'),
            ]);
    }
}
