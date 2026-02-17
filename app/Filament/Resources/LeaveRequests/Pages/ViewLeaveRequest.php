<?php

namespace App\Filament\Resources\LeaveRequests\Pages;

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewLeaveRequest extends ViewRecord
{
    protected static string $resource = LeaveRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Request')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Leave Request')
                ->modalDescription('Are you sure you want to approve this leave request?')
                ->hidden(fn () => ! $this->record->canBeApproved())
                ->action(function () {
                    $record = $this->record;

                    $record->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);

                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'user_name' => Auth::user()?->name,
                        'action' => 'approved',
                        'auditable_type' => $record->getMorphClass(),
                        'auditable_id' => $record->getKey(),
                        'auditable_label' => "Leave Request {$record->request_number}",
                        'old_values' => ['status' => 'pending'],
                        'new_values' => ['status' => 'approved'],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('Leave Request Approved!')
                        ->body("Leave request {$record->request_number} has been approved.")
                        ->success()
                        ->send();

                    $this->redirect(LeaveRequestResource::getUrl('view', ['record' => $record]));
                }),

            Action::make('reject')
                ->label('Reject Request')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Reason for Rejection')
                        ->required()
                        ->rows(3)
                        ->placeholder('Please provide a reason for rejecting this request...'),
                ])
                ->hidden(fn () => ! $this->record->canBeRejected())
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'rejected',
                        'rejection_reason' => $data['rejection_reason'],
                        'rejected_at' => now(),
                    ]);

                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'user_name' => Auth::user()?->name,
                        'action' => 'rejected',
                        'auditable_type' => $this->record->getMorphClass(),
                        'auditable_id' => $this->record->getKey(),
                        'auditable_label' => "Leave Request {$this->record->request_number}",
                        'old_values' => ['status' => 'pending'],
                        'new_values' => ['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('Leave Request Rejected')
                        ->body('The leave request has been rejected.')
                        ->success()
                        ->send();

                    $this->redirect(LeaveRequestResource::getUrl('view', ['record' => $this->record]));
                }),

            EditAction::make()
                ->hidden(fn () => $this->record->status->value !== 'pending'),

            DeleteAction::make()
                ->hidden(fn () => $this->record->status->value !== 'pending'),
        ];
    }
}
