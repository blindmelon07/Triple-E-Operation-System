<?php

namespace App\Filament\Resources\MaintenanceTypes;

use App\Filament\Resources\MaintenanceTypes\Pages\CreateMaintenanceType;
use App\Filament\Resources\MaintenanceTypes\Pages\EditMaintenanceType;
use App\Filament\Resources\MaintenanceTypes\Pages\ListMaintenanceTypes;
use App\Filament\Resources\MaintenanceTypes\Schemas\MaintenanceTypeForm;
use App\Filament\Resources\MaintenanceTypes\Tables\MaintenanceTypesTable;
use App\Models\MaintenanceType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaintenanceTypeResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Fleet Management';

    protected static ?string $model = MaintenanceType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Service Types';

    public static function form(Schema $schema): Schema
    {
        return MaintenanceTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceTypesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceTypes::route('/'),
            'create' => CreateMaintenanceType::route('/create'),
            'edit' => EditMaintenanceType::route('/{record}/edit'),
        ];
    }
}
