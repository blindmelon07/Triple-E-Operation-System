<?php

namespace App\Filament\Resources\CashRegisterSessions;

use App\Filament\Resources\CashRegisterSessions\Pages\ListCashRegisterSessions;
use App\Filament\Resources\CashRegisterSessions\Pages\ViewCashRegisterSession;
use App\Filament\Resources\CashRegisterSessions\Schemas\CashRegisterSessionForm;
use App\Filament\Resources\CashRegisterSessions\Tables\CashRegisterSessionsTable;
use App\Models\CashRegisterSession;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashRegisterSessionResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Inventory & Sales';
    protected static ?string $model = CashRegisterSession::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?string $navigationLabel = 'Cash Register';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return CashRegisterSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashRegisterSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashRegisterSessions::route('/'),
            'view' => ViewCashRegisterSession::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
