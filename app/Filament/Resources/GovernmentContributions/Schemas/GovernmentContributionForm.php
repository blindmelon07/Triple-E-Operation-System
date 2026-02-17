<?php

namespace App\Filament\Resources\GovernmentContributions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GovernmentContributionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contribution Details')
                    ->schema([
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'sss' => 'SSS',
                                'philhealth' => 'PhilHealth',
                                'pagibig' => 'Pag-IBIG',
                            ])
                            ->required(),

                        TextInput::make('salary_from')
                            ->label('Salary From')
                            ->numeric()
                            ->required()
                            ->prefix('₱')
                            ->minValue(0),

                        TextInput::make('salary_to')
                            ->label('Salary To')
                            ->numeric()
                            ->required()
                            ->prefix('₱')
                            ->minValue(0),

                        TextInput::make('employee_share')
                            ->label('Employee Share')
                            ->numeric()
                            ->required()
                            ->prefix('₱')
                            ->minValue(0),

                        TextInput::make('employer_share')
                            ->label('Employer Share')
                            ->numeric()
                            ->required()
                            ->prefix('₱')
                            ->minValue(0),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(3),
            ]);
    }
}
