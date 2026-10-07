<?php

namespace App\Filament\Resources\Incentives\Schemas;

use App\Models\Employee;
use App\Models\Incentive;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IncentiveForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sales Incentive')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->default(fn () => Incentive::generateReferenceNumber())
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('date')
                            ->label('Date')
                            ->required()
                            ->default(now())
                            ->helperText('Payroll picks it up in the first cut-off ending on or after this date.'),

                        Select::make('employee_id')
                            ->label('Sales Representative')
                            ->options(fn () => Employee::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->getOptionLabelUsing(fn ($value) => Employee::find($value)?->name)
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0.01)
                            ->required(),

                        TextInput::make('description')
                            ->label('Description')
                            ->placeholder('e.g. September 2026 sales incentive — ₱1.2M collected')
                            ->maxLength(255)
                            ->required()
                            ->columnSpanFull(),

                        Radio::make('payout_method')
                            ->label('Payout')
                            ->options(Incentive::payoutOptions())
                            ->descriptions([
                                Incentive::PAYOUT_PAYROLL => "Included in the employee's next generated payroll.",
                                Incentive::PAYOUT_SEPARATE => 'Handed over on its own; mark it as released when paid.',
                            ])
                            ->default(Incentive::PAYOUT_PAYROLL)
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
