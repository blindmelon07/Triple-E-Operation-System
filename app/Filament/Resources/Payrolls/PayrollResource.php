<?php

namespace App\Filament\Resources\Payrolls;

use App\Enums\PayrollStatus;
use App\Filament\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Resources\Payrolls\Pages\ListPayrolls;
use App\Filament\Resources\Payrolls\Pages\ViewPayroll;
use App\Filament\Resources\Payrolls\RelationManagers\PayrollItemsRelationManager;
use App\Filament\Resources\Payrolls\Schemas\PayrollForm;
use App\Filament\Resources\Payrolls\Tables\PayrollsTable;
use App\Models\Payroll;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PayrollResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $model = Payroll::class;
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Payrolls';

    public static function getNavigationBadge(): ?string
    {
        $count = Payroll::where('status', PayrollStatus::Draft)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return PayrollForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PayrollsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PayrollItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrolls::route('/'),
            'create' => CreatePayroll::route('/create'),
            'view' => ViewPayroll::route('/{record}'),
            'edit' => EditPayroll::route('/{record}/edit'),
        ];
    }
}
