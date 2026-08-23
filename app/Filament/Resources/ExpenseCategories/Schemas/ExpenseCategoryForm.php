<?php

namespace App\Filament\Resources\ExpenseCategories\Schemas;

use App\Enums\ExpenseReportGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ExpenseCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Category Name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->maxLength(500),

                Select::make('report_group')
                    ->label('Report Group')
                    ->options(collect(ExpenseReportGroup::cases())->mapWithKeys(
                        fn (ExpenseReportGroup $case) => [$case->value => $case->label()]
                    ))
                    ->default(ExpenseReportGroup::Other->value)
                    ->required()
                    ->helperText('Which section this category\'s expenses are grouped under on the Daily/Period Transaction Reports.'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive categories will not appear in expense forms'),
            ]);
    }
}
