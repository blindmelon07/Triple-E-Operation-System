<?php

namespace App\Filament\Resources\AuditLogs\Pages;

use App\Exports\AuditLogsExport;
use App\Filament\Resources\AuditLogs\AuditLogResource;
use App\Models\User;
use App\Services\CsvExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export Report')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Export audit log report')
                ->modalSubmitActionLabel('Download CSV')
                ->form([
                    Select::make('format')
                        ->label('Report')
                        ->options([
                            'summary' => 'Summary per user per day',
                            'detailed' => 'Detailed (every log entry)',
                        ])
                        ->default('summary')
                        ->required(),
                    Select::make('user_id')
                        ->label('User')
                        ->options(fn () => User::orderBy('name')->pluck('name', 'id')->toArray())
                        ->default(fn () => $this->tableFilters['user_id']['value'] ?? null)
                        ->placeholder('All users')
                        ->searchable(),
                    DatePicker::make('date_from')
                        ->label('From Date')
                        ->default(fn () => $this->tableFilters['date_range']['from'] ?? today()->startOfMonth())
                        ->required(),
                    DatePicker::make('date_until')
                        ->label('To Date')
                        ->default(fn () => $this->tableFilters['date_range']['until'] ?? today())
                        ->afterOrEqual('date_from')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $export = new AuditLogsExport(
                        userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
                        dateFrom: $data['date_from'],
                        dateUntil: $data['date_until'],
                        summary: $data['format'] === 'summary',
                    );

                    return (new CsvExportService)->export(
                        $export->getHeaders(),
                        $export->getData(),
                        $export->getFilename(),
                    );
                }),
        ];
    }
}
