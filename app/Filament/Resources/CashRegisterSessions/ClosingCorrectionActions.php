<?php

namespace App\Filament\Resources\CashRegisterSessions;

use App\Enums\CashRegisterStatus;
use App\Models\CashRegisterSession;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

/**
 * Fixing the closing amount of a register session that is already closed —
 * shared by the sessions table and the session's view page.
 */
class ClosingCorrectionActions
{
    private static function isManager(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'super_admin']);
    }

    /** @return array<Action> */
    public static function all(): array
    {
        return [self::request(), self::approve(), self::reject()];
    }

    public static function request(): Action
    {
        return Action::make('request_closing_correction')
            ->label('Request Correction')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->visible(fn (CashRegisterSession $record) => $record->status === CashRegisterStatus::Closed
                && ! $record->pendingClosingCorrection()
                && (self::isManager() || $record->user_id === auth()->id()))
            ->modalHeading('Correct Closing Amount')
            ->modalDescription(fn () => self::isManager()
                ? 'As a manager, your correction is applied immediately.'
                : 'A manager has to approve this before the closing amount changes.')
            ->form([
                Placeholder::make('current')
                    ->label('Recorded closing amount')
                    ->content(fn (CashRegisterSession $record) => '₱' . number_format((float) $record->closing_amount, 2)
                        . ' (expected ₱' . number_format((float) $record->expected_amount, 2) . ')'),
                TextInput::make('corrected_amount')
                    ->label('Actual cash counted')
                    ->prefix('₱')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Textarea::make('reason')
                    ->label('Reason')
                    ->placeholder('e.g. Forgot to enter the count when closing; drawer had ₱5,000')
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (CashRegisterSession $record, array $data) {
                $correction = $record->requestClosingCorrection(auth()->user(), (float) $data['corrected_amount'], $data['reason']);

                Notification::make()
                    ->success()
                    ->title($correction->status === 'approved' ? 'Closing amount corrected' : 'Correction submitted for manager approval')
                    ->send();
            });
    }

    public static function approve(): Action
    {
        return Action::make('approve_closing_correction')
            ->label('Approve Correction')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (CashRegisterSession $record) => self::isManager() && $record->pendingClosingCorrection())
            ->requiresConfirmation()
            ->modalHeading('Approve Closing Correction')
            ->modalDescription(function (CashRegisterSession $record) {
                $correction = $record->pendingClosingCorrection();

                return "{$correction->requestedBy?->name} asks to change the closing amount from ₱"
                    . number_format((float) $correction->original_closing_amount, 2) . ' to ₱'
                    . number_format((float) $correction->corrected_amount, 2) . ". Reason: {$correction->reason}";
            })
            ->action(function (CashRegisterSession $record) {
                $record->pendingClosingCorrection()?->approve(auth()->user());

                Notification::make()->success()->title('Closing amount corrected')->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject_closing_correction')
            ->label('Reject Correction')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (CashRegisterSession $record) => self::isManager() && $record->pendingClosingCorrection())
            ->modalHeading('Reject Closing Correction')
            ->form([
                Textarea::make('rejection_reason')
                    ->label('Reason for rejecting')
                    ->maxLength(1000),
            ])
            ->action(function (CashRegisterSession $record, array $data) {
                $record->pendingClosingCorrection()?->reject(auth()->user(), $data['rejection_reason'] ?? null);

                Notification::make()->success()->title('Correction rejected')->send();
            });
    }
}
