<?php

namespace App\Filament\Resources\GovernmentContributions;

use App\Filament\Resources\GovernmentContributions\Pages\CreateGovernmentContribution;
use App\Filament\Resources\GovernmentContributions\Pages\EditGovernmentContribution;
use App\Filament\Resources\GovernmentContributions\Pages\ListGovernmentContributions;
use App\Filament\Resources\GovernmentContributions\Schemas\GovernmentContributionForm;
use App\Filament\Resources\GovernmentContributions\Tables\GovernmentContributionsTable;
use App\Models\GovernmentContribution;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GovernmentContributionResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $model = GovernmentContribution::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Government Contributions';

    public static function form(Schema $schema): Schema
    {
        return GovernmentContributionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GovernmentContributionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGovernmentContributions::route('/'),
            'create' => CreateGovernmentContribution::route('/create'),
            'edit' => EditGovernmentContribution::route('/{record}/edit'),
        ];
    }
}
