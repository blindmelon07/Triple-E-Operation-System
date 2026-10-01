<?php

namespace App\Filament\Resources\DateChangeRequests\Tables;

use App\Models\DateChangeRequest;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use RuntimeException;

class DateChangeRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),

                TextColumn::make('record_label')
                    ->label('Record'),

                TextColumn::make('old_date')
                    ->label('Current Date')
                    ->date('M d, Y'),

                TextColumn::make('new_date')
                    ->label('New Date')
                    ->date('M d, Y')
                    ->weight('bold'),

                TextColumn::make('reason')
                    ->label('Reason')
                    ->wrap()
                    ->limit(60),

                TextColumn::make('requestedBy.name')
                    ->label('Requested By'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'  => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->description(fn (DateChangeRequest $record) => $record->rejection_reason),

                TextColumn::make('reviewedBy.name')
                    ->label('Reviewed By')
                    ->description(fn (DateChangeRequest $record) => $record->reviewed_at?->format('M d, Y h:i A'))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription(fn (DateChangeRequest $record) => "Change the date of {$record->record_label} from "
                        .($record->old_date?->format('M d, Y') ?? '—').' to '.$record->new_date->format('M d, Y').'?')
                    ->visible(fn (DateChangeRequest $record) => $record->status === 'pending'
                        && DateChangeRequest::canApprove(auth()->user()))
                    ->action(function (DateChangeRequest $record) {
                        try {
                            $record->approve();
                        } catch (RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Date change approved')->success()->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label('Reason for rejecting')
                            ->maxLength(255),
                    ])
                    ->visible(fn (DateChangeRequest $record) => $record->status === 'pending'
                        && DateChangeRequest::canApprove(auth()->user()))
                    ->action(function (DateChangeRequest $record, array $data) {
                        try {
                            $record->reject($data['rejection_reason'] ?? null);
                        } catch (RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }

                        Notification::make()->title('Date change rejected')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
