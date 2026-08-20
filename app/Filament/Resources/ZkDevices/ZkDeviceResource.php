<?php

namespace App\Filament\Resources\ZkDevices;

use App\Filament\Resources\ZkDevices\Pages\CreateZkDevice;
use App\Filament\Resources\ZkDevices\Pages\EditZkDevice;
use App\Filament\Resources\ZkDevices\Pages\ListZkDevices;
use App\Filament\Resources\ZkDevices\Schemas\ZkDeviceForm;
use App\Filament\Resources\ZkDevices\Tables\ZkDevicesTable;
use App\Models\ZkDevice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ZkDeviceResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Attendance Management';

    protected static ?string $model = ZkDevice::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Biometric Devices';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ZkDeviceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ZkDevicesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListZkDevices::route('/'),
            'create' => CreateZkDevice::route('/create'),
            'edit' => EditZkDevice::route('/{record}/edit'),
        ];
    }
}
