<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use App\Support\AuditLogPresenter;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Authentication';

    protected static ?string $model = AuditLog::class;
    protected static bool $shouldRegisterNavigation = false;

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
        return $schema->columns(1)->components([
            Section::make()
                ->schema([
                    ViewEntry::make('header')
                        ->hiddenLabel()
                        ->view('filament.infolists.audit-log-header'),
                    Grid::make(['default' => 1, 'sm' => 3])
                        ->schema([
                            TextEntry::make('ip_address')
                                ->label('IP Address')
                                ->icon(Heroicon::OutlinedGlobeAlt)
                                ->placeholder('-')
                                ->copyable(),
                            TextEntry::make('device')
                                ->label('Device')
                                ->icon(Heroicon::OutlinedComputerDesktop),
                            TextEntry::make('auditable_id')
                                ->label('Record ID')
                                ->icon(Heroicon::OutlinedHashtag)
                                ->placeholder('-'),
                        ]),
                ]),

            Section::make(fn (AuditLog $record) => (new AuditLogPresenter($record))->mode() === 'update' ? 'What changed' : 'Details')
                ->icon(Heroicon::OutlinedListBullet)
                ->schema([
                    ViewEntry::make('changes')
                        ->hiddenLabel()
                        ->view('filament.infolists.audit-log-changes'),
                ])
                ->visible(fn (AuditLog $record) => filled($record->old_values) || filled($record->new_values)),

            Section::make('Technical details')
                ->icon(Heroicon::OutlinedCodeBracket)
                ->collapsed()
                ->schema([
                    TextEntry::make('user_agent')
                        ->label('Browser (raw)')
                        ->placeholder('-')
                        ->fontFamily(FontFamily::Mono)
                        ->size(TextSize::Small),
                    TextEntry::make('action')
                        ->label('Action key')
                        ->fontFamily(FontFamily::Mono),
                    TextEntry::make('auditable_type')
                        ->label('Model class')
                        ->placeholder('-')
                        ->fontFamily(FontFamily::Mono),
                ]),
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
