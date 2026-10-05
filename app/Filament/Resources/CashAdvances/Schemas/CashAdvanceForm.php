<?php

namespace App\Filament\Resources\CashAdvances\Schemas;

use App\Models\CashAdvance;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cash Advance')
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->default(fn () => CashAdvance::generateReferenceNumber())
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('date_granted')
                            ->label('Date Granted')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(fn () => Employee::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->getOptionLabelUsing(fn ($value) => Employee::find($value)?->name)
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            // Can't shrink an advance below what has already
                            // been repaid — that would leave a negative balance.
                            ->minValue(fn (?CashAdvance $record) => max(0.01, $record?->totalPaid() ?? 0)),

                        TextInput::make('deduction_per_payroll')
                            ->label('Deduction per Payroll')
                            ->helperText('Deducted automatically on every generated payroll until fully paid. Set to 0 to stop automatic deductions.')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0),

                        TextInput::make('purpose')
                            ->label('Purpose')
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
