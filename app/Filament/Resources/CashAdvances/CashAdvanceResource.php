<?php

namespace App\Filament\Resources\CashAdvances;

use App\Filament\Resources\CashAdvances\Pages\CreateCashAdvance;
use App\Filament\Resources\CashAdvances\Pages\EditCashAdvance;
use App\Filament\Resources\CashAdvances\Pages\ListCashAdvances;
use App\Filament\Resources\CashAdvances\Pages\ViewCashAdvance;
use App\Filament\Resources\CashAdvances\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\CashAdvances\Schemas\CashAdvanceForm;
use App\Filament\Resources\CashAdvances\Tables\CashAdvancesTable;
use App\Models\CashAdvance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CashAdvanceResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $model = CashAdvance::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Cash Advances';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return CashAdvanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashAdvancesTable::configure($table);
    }

    /**
     * Every listing needs the paid-to-date figure for the Paid/Balance
     * columns and the status filter — load it once as a subquery sum.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withSum(['payments as paid_amount' => fn (Builder $q) => $q->effective()], 'amount');
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashAdvances::route('/'),
            'create' => CreateCashAdvance::route('/create'),
            'view' => ViewCashAdvance::route('/{record}'),
            'edit' => EditCashAdvance::route('/{record}/edit'),
        ];
    }
}
