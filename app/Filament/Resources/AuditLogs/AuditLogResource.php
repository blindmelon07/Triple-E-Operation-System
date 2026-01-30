<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Authentication';

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'Audit Logs';

    protected static ?string $pluralModelLabel = 'Audit Logs';

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Audit Details')
                ->schema([
                    TextEntry::make('user_display_name')
                        ->label('User'),
                    TextEntry::make('action')
                        ->label('Action')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'created' => 'success',
                            'updated' => 'info',
                            'deleted' => 'danger',
                            'login'   => 'primary',
                            'logout'  => 'gray',
                            default   => 'gray',
                        })
                        ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                    TextEntry::make('auditable_label')
                        ->label('Record'),
                    TextEntry::make('auditable_type')
                        ->label('Model')
                        ->formatStateUsing(fn (?string $state): string =>
                            $state ? class_basename($state) : '-'
                        ),
                    TextEntry::make('ip_address')
                        ->label('IP Address'),
                    TextEntry::make('user_agent')
                        ->label('Browser'),
                    TextEntry::make('created_at')
                        ->label('Timestamp')
                        ->dateTime('M d, Y H:i:s'),
                ])->columns(2),

            Section::make('Changes')
                ->schema([
                    KeyValueEntry::make('old_values')
                        ->label('Old Values'),
                    KeyValueEntry::make('new_values')
                        ->label('New Values'),
                ])->columns(2)
                ->visible(fn ($record) =>
                    $record->old_values !== null || $record->new_values !== null
                ),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view'  => ViewAuditLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
