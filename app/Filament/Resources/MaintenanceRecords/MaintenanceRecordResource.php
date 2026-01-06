<?php

namespace App\Filament\Resources\MaintenanceRecords;

use App\Filament\Resources\MaintenanceRecords\Pages\CreateMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecords\Pages\EditMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecords\Pages\ListMaintenanceRecords;
use App\Filament\Resources\MaintenanceRecords\Pages\ViewMaintenanceRecord;
use App\Filament\Resources\MaintenanceRecords\Schemas\MaintenanceRecordForm;
use App\Filament\Resources\MaintenanceRecords\Tables\MaintenanceRecordsTable;
use App\Models\MaintenanceRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MaintenanceRecordResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Fleet Management';

    protected static ?string $model = MaintenanceRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Service Records';

    public static function form(Schema $schema): Schema
    {
        return MaintenanceRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceRecordsTable::configure($table);
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
            'index' => ListMaintenanceRecords::route('/'),
            'create' => CreateMaintenanceRecord::route('/create'),
            'view' => ViewMaintenanceRecord::route('/{record}'),
            'edit' => EditMaintenanceRecord::route('/{record}/edit'),
        ];
    }
}
