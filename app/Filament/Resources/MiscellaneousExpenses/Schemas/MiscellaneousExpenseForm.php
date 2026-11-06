<?php

namespace App\Filament\Resources\MiscellaneousExpenses\Schemas;

use App\Models\MiscellaneousExpense;
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

class MiscellaneousExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Information')
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->default(fn () => MiscellaneousExpense::generateReferenceNumber())
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('expense_date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Toll fee, parking, car wash, registration')
                            ->columnSpanFull(),

                        TextInput::make('payee')
                            ->label('Paid To')
                            ->maxLength(255),

                        TextInput::make('si_dr_number')
                            ->label('SI/DR / OR #')
                            ->helperText('Sales invoice / delivery receipt / official receipt number.')
                            ->maxLength(255),

                        Select::make('vehicle_id')
                            ->label('Truck/Unit')
                            ->helperText('Optional — leave blank if not for a specific vehicle.')
                            ->options(
                                Vehicle::whereIn('status', ['active', 'maintenance'])
                                    ->get()
                                    ->mapWithKeys(fn ($v) => [$v->id => $v->full_name])
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set, $state) => $set(
                                'plate_number_display',
                                $state ? Vehicle::find($state)?->plate_number : null
                            )),

                        TextInput::make('plate_number_display')
                            ->label('Plate Number')
                            ->disabled()
                            ->dehydrated(false)
                            ->afterStateHydrated(fn (Set $set, Get $get) => $set(
                                'plate_number_display',
                                $get('vehicle_id') ? Vehicle::find($get('vehicle_id'))?->plate_number : null
                            )),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->prefix('₱'),
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
                            ->directory('miscellaneous-receipts')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/*', 'application/pdf']),
                    ])
                    ->columns(1)
                    ->collapsed(),
            ]);
    }
}
