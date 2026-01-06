<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Supplier Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('payment_term_days')
                            ->label('Payment Terms')
                            ->options([
                                0 => 'COD (Cash on Delivery)',
                                7 => 'Net 7',
                                15 => 'Net 15',
                                30 => 'Net 30',
                                45 => 'Net 45',
                                60 => 'Net 60',
                                90 => 'Net 90',
                            ])
                            ->default(0)
                            ->required(),
                        TextInput::make('contact_person')
                            ->maxLength(255),
                    ])->columns(3),
                Section::make('Contact Details')
                    ->schema([
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
