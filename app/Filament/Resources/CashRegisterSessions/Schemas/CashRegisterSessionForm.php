<?php

namespace App\Filament\Resources\CashRegisterSessions\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CashRegisterSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session Details')
                    ->schema([
                        \Filament\Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Cashier')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('opening_amount')
                            ->prefix('₱')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('closing_amount')
                            ->prefix('₱')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('expected_amount')
                            ->prefix('₱')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('discrepancy')
                            ->prefix('₱')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('total_sales')
                            ->prefix('₱')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('total_cash_sales')
                            ->prefix('₱')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('total_transactions')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('status')
                            ->disabled(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }
}
