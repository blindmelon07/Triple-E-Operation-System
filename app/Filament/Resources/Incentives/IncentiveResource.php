<?php

namespace App\Filament\Resources\Incentives;

use App\Filament\Resources\Incentives\Pages\CreateIncentive;
use App\Filament\Resources\Incentives\Pages\EditIncentive;
use App\Filament\Resources\Incentives\Pages\ListIncentives;
use App\Filament\Resources\Incentives\Schemas\IncentiveForm;
use App\Filament\Resources\Incentives\Tables\IncentivesTable;
use App\Models\Incentive;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class IncentiveResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $model = Incentive::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Sales Incentives';

    protected static ?string $modelLabel = 'Sales Incentive';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public static function form(Schema $schema): Schema
    {
        return IncentiveForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IncentivesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['employee', 'payrollItem.payroll']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIncentives::route('/'),
            'create' => CreateIncentive::route('/create'),
            'edit' => EditIncentive::route('/{record}/edit'),
        ];
    }
}
