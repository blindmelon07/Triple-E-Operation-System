<?php

namespace App\Filament\Resources\EmployeeCompensations;

use App\Filament\Resources\EmployeeCompensations\Pages\CreateEmployeeCompensation;
use App\Filament\Resources\EmployeeCompensations\Pages\EditEmployeeCompensation;
use App\Filament\Resources\EmployeeCompensations\Pages\ListEmployeeCompensations;
use App\Filament\Resources\EmployeeCompensations\Schemas\EmployeeCompensationForm;
use App\Filament\Resources\EmployeeCompensations\Tables\EmployeeCompensationsTable;
use App\Models\EmployeeCompensation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EmployeeCompensationResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Payroll';

    protected static ?string $model = EmployeeCompensation::class;
    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Employee Compensation';

    public static function form(Schema $schema): Schema
    {
        return EmployeeCompensationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeeCompensationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployeeCompensations::route('/'),
            'create' => CreateEmployeeCompensation::route('/create'),
            'edit' => EditEmployeeCompensation::route('/{record}/edit'),
        ];
    }
}
