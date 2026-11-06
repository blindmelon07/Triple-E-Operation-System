<?php

namespace App\Filament\Resources\MiscellaneousExpenses;

use App\Filament\Resources\MiscellaneousExpenses\Pages\CreateMiscellaneousExpense;
use App\Filament\Resources\MiscellaneousExpenses\Pages\EditMiscellaneousExpense;
use App\Filament\Resources\MiscellaneousExpenses\Pages\ListMiscellaneousExpenses;
use App\Filament\Resources\MiscellaneousExpenses\Pages\ViewMiscellaneousExpense;
use App\Filament\Resources\MiscellaneousExpenses\Schemas\MiscellaneousExpenseForm;
use App\Filament\Resources\MiscellaneousExpenses\Tables\MiscellaneousExpensesTable;
use App\Models\MiscellaneousExpense;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MiscellaneousExpenseResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Fleet Management';

    protected static ?string $model = MiscellaneousExpense::class;

    // Reached from the Home page's Fleet & Maintenance cards, like Fuel Logs.
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Miscellaneous Expenses';

    protected static ?string $modelLabel = 'Miscellaneous Expense';

    protected static ?string $pluralModelLabel = 'Miscellaneous Expenses';

    public static function form(Schema $schema): Schema
    {
        return MiscellaneousExpenseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MiscellaneousExpensesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMiscellaneousExpenses::route('/'),
            'create' => CreateMiscellaneousExpense::route('/create'),
            'view' => ViewMiscellaneousExpense::route('/{record}'),
            'edit' => EditMiscellaneousExpense::route('/{record}/edit'),
        ];
    }
}
