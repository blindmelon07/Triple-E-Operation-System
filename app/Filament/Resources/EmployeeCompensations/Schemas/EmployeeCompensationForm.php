<?php

namespace App\Filament\Resources\EmployeeCompensations\Schemas;

use App\Enums\PayPeriodType;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeCompensationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Employee & Rate')
                    ->schema([
                        Select::make('user_id')
                            ->label('Employee')
                            ->options(
                                User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('daily_rate')
                            ->label('Daily Rate')
                            ->numeric()
                            ->required()
                            ->prefix('₱')
                            ->minValue(0),

                        Select::make('pay_period')
                            ->label('Pay Period')
                            ->options(
                                collect(PayPeriodType::cases())
                                    ->mapWithKeys(fn ($p) => [$p->value => $p->getLabel()])
                            )
                            ->default(PayPeriodType::SemiMonthly->value)
                            ->required(),

                        Select::make('days_off')
                            ->label('Day(s) Off Per Week')
                            ->multiple()
                            ->options([
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                                'sunday' => 'Sunday',
                            ])
                            ->default(['sunday'])
                            ->helperText('Select the employee\'s weekly rest day(s)'),
                    ])
                    ->columns(2),

                Section::make('Overtime & Late Settings')
                    ->schema([
                        TextInput::make('overtime_rate_multiplier')
                            ->label('Overtime Rate Multiplier')
                            ->numeric()
                            ->default(1.25)
                            ->suffix('x')
                            ->minValue(1)
                            ->step(0.05),

                        Select::make('late_deduction_type')
                            ->label('Late Deduction Type')
                            ->options([
                                'per_minute' => 'Per Minute (daily_rate / 480 per min)',
                                'fixed' => 'Fixed Amount per Late',
                            ])
                            ->default('per_minute')
                            ->required(),

                        TextInput::make('late_deduction_amount')
                            ->label('Late Deduction Amount')
                            ->numeric()
                            ->default(0)
                            ->prefix('₱')
                            ->helperText('For per_minute: rate per minute. For fixed: flat amount per late occurrence.'),
                    ])
                    ->columns(3),

                Section::make('Allowances')
                    ->schema([
                        TextInput::make('allowance')
                            ->label('Allowance')
                            ->numeric()
                            ->default(0)
                            ->prefix('₱'),

                        Textarea::make('allowance_description')
                            ->label('Allowance Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
