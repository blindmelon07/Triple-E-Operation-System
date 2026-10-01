<?php

namespace App\Filament\Resources\MaintenanceRecords\Actions;

use App\Models\DateChangeRequest;
use App\Models\MaintenanceRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Lets a non-admin ask for a maintenance record's date to be changed. The
 * date only changes once an admin approves it under Date Change Requests.
 * Admins don't see this — they can edit the date directly on the form.
 */
class RequestDateChangeAction
{
    public static function make(): Action
    {
        return Action::make('requestDateChange')
            ->label('Request Date Change')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->color('warning')
            ->visible(fn (MaintenanceRecord $record) => ! DateChangeRequest::canApprove(auth()->user())
                && auth()->user()?->can('update', $record))
            ->disabled(fn (MaintenanceRecord $record) => $record->pendingDateChangeRequest !== null)
            ->tooltip(fn (MaintenanceRecord $record) => $record->pendingDateChangeRequest
                ? 'A date change is already waiting for admin approval.'
                : null)
            ->modalDescription(fn (MaintenanceRecord $record) => 'Current date: '.($record->maintenance_date?->format('M d, Y') ?? '—').'. An admin must approve the change before it shows on reports.')
            ->schema([
                DatePicker::make('new_date')
                    ->label('New Date')
                    ->required()
                    ->maxDate(now()->addMonth()),
                Textarea::make('reason')
                    ->label('Reason')
                    ->placeholder('e.g. Encoded late — actual SI/DR date is Sept 15')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (MaintenanceRecord $record, array $data) {
                if ($record->pendingDateChangeRequest()->exists()) {
                    Notification::make()->title('A date change is already pending for this record.')->warning()->send();

                    return;
                }

                if ($record->maintenance_date?->toDateString() === $data['new_date']) {
                    Notification::make()->title('That is already the record\'s date.')->warning()->send();

                    return;
                }

                DateChangeRequest::submit($record, 'maintenance_date', $data['new_date'], $data['reason']);

                Notification::make()
                    ->title('Date change requested')
                    ->body('An admin needs to approve it before the date changes.')
                    ->success()
                    ->send();
            });
    }
}
