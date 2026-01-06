<?php

namespace App\Filament\Resources\MaintenanceRequests\Schemas;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceType;
use App\Models\Vehicle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Information')
                    ->schema([
                        TextInput::make('request_number')
                            ->label('Request Number')
                            ->default(fn () => MaintenanceRequest::generateRequestNumber())
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
                                        $set('current_mileage', $vehicle->current_mileage);
                                    }
                                }
                            }),

                        Select::make('maintenance_type_id')
                            ->label('Service Type')
                            ->relationship('maintenanceType', 'name')
                            ->options(MaintenanceType::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('priority')
                            ->label('Priority')
                            ->options([
                                'low' => '🟢 Low',
                                'normal' => '🔵 Normal',
                                'high' => '🟠 High',
                                'urgent' => '🔴 Urgent',
                            ])
                            ->default('normal')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Request Details')
                    ->schema([
                        TextInput::make('current_mileage')
                            ->label('Current Mileage')
                            ->numeric()
                            ->suffix('km')
                            ->helperText('Current odometer reading'),

                        DatePicker::make('preferred_date')
                            ->label('Preferred Service Date')
                            ->minDate(now())
                            ->helperText('When would you like this service done?'),

                        TextInput::make('estimated_cost')
                            ->label('Estimated Cost (Optional)')
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0),

                        Textarea::make('description')
                            ->label('Description / Reason')
                            ->required()
                            ->rows(4)
                            ->maxLength(2000)
                            ->placeholder('Describe the issue or reason for this maintenance request...')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Status Information')
                    ->schema([
                        Placeholder::make('status_display')
                            ->label('Status')
                            ->content(fn ($record) => $record?->status ? ucfirst($record->status) : 'Pending'),

                        Placeholder::make('requested_by_display')
                            ->label('Requested By')
                            ->content(fn ($record) => $record?->requestedBy?->name ?? '-'),

                        Placeholder::make('approved_by_display')
                            ->label('Approved By')
                            ->content(fn ($record) => $record?->approvedBy?->name ?? '-'),

                        Placeholder::make('created_at_display')
                            ->label('Requested At')
                            ->content(fn ($record) => $record?->created_at?->format('M d, Y H:i') ?? '-'),
                    ])
                    ->columns(4)
                    ->hidden(fn ($record) => ! $record),

                Section::make('Rejection Details')
                    ->schema([
                        Placeholder::make('rejection_reason_display')
                            ->label('Rejection Reason')
                            ->content(fn ($record) => $record?->rejection_reason ?? '-'),

                        Placeholder::make('rejected_at_display')
                            ->label('Rejected At')
                            ->content(fn ($record) => $record?->rejected_at?->format('M d, Y H:i') ?? '-'),
                    ])
                    ->columns(2)
                    ->hidden(fn ($record) => $record?->status !== 'rejected'),
            ]);
    }
}
