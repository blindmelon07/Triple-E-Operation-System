<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Details')
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->default(fn () => Expense::generateReferenceNumber())
                            ->disabled()
                            ->dehydrated(),

                        Select::make('expense_category_id')
                            ->label('Category')
                            ->relationship('category', 'name')
                            ->options(ExpenseCategory::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Textarea::make('description')
                                    ->rows(2),
                            ]),

                        DatePicker::make('expense_date')
                            ->label('Expense Date')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->minValue(0.01)
                            ->step(0.01),

                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'cash' => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'check' => 'Check',
                                'credit_card' => 'Credit Card',
                                'gcash' => 'GCash',
                                'maya' => 'Maya',
                            ])
                            ->default('cash')
                            ->required(),

                        TextInput::make('payee')
                            ->label('Payee / Vendor')
                            ->maxLength(255)
                            ->placeholder('Who was paid?'),
                    ])
                    ->columns(2),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Describe the expense...'),

                        FileUpload::make('receipt_path')
                            ->label('Receipt / Attachment')
                            ->directory('expense-receipts')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/*', 'application/pdf']),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending Approval',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('approved')
                            ->required(),
                    ])
                    ->columns(1),
            ]);
    }
}
