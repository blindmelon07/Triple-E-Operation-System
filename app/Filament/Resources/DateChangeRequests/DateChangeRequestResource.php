<?php

namespace App\Filament\Resources\DateChangeRequests;

use App\Filament\Resources\DateChangeRequests\Pages\ListDateChangeRequests;
use App\Filament\Resources\DateChangeRequests\Tables\DateChangeRequestsTable;
use App\Models\DateChangeRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Admins review pending date changes here. Anyone else who opens it only
 * sees the requests they filed themselves, so they can check the status.
 */
class DateChangeRequestResource extends Resource
{
    protected static ?string $model = DateChangeRequest::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Date Change Requests';

    protected static ?string $pluralModelLabel = 'Date Change Requests';

    public static function table(Table $table): Table
    {
        return DateChangeRequestsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['record', 'requestedBy', 'reviewedBy']);

        if (! DateChangeRequest::canApprove(auth()->user())) {
            $query->where('requested_by_id', auth()->id());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDateChangeRequests::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
