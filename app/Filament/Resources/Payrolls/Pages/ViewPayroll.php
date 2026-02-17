<?php

namespace App\Filament\Resources\Payrolls\Pages;

use App\Enums\PayrollStatus;
use App\Filament\Resources\Payrolls\PayrollResource;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewPayroll extends ViewRecord
{
    protected static string $resource = PayrollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Payroll')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Payroll')
                ->modalDescription('Are you sure you want to approve this payroll? This will lock all payroll items.')
                ->hidden(fn () => ! $this->record->canBeApproved())
                ->action(function () {
                    $record = $this->record;

                    $record->update([
                        'status' => PayrollStatus::Approved,
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);

                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'user_name' => Auth::user()?->name,
                        'action' => 'approved',
                        'auditable_type' => $record->getMorphClass(),
                        'auditable_id' => $record->getKey(),
                        'auditable_label' => "Payroll {$record->payroll_number}",
                        'old_values' => ['status' => 'draft'],
                        'new_values' => ['status' => 'approved'],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('Payroll Approved!')
                        ->body("Payroll {$record->payroll_number} has been approved.")
                        ->success()
                        ->send();

                    $this->redirect(PayrollResource::getUrl('view', ['record' => $record]));
                }),

            Action::make('mark_paid')
                ->label('Mark as Paid')
                ->icon('heroicon-o-banknotes')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Mark Payroll as Paid')
                ->modalDescription('Are you sure you want to mark this payroll as paid?')
                ->hidden(fn () => ! $this->record->canBePaid())
                ->action(function () {
                    $record = $this->record;

                    $record->update([
                        'status' => PayrollStatus::Paid,
                        'paid_at' => now(),
                    ]);

                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'user_name' => Auth::user()?->name,
                        'action' => 'marked_paid',
                        'auditable_type' => $record->getMorphClass(),
                        'auditable_id' => $record->getKey(),
                        'auditable_label' => "Payroll {$record->payroll_number}",
                        'old_values' => ['status' => 'approved'],
                        'new_values' => ['status' => 'paid'],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('Payroll Marked as Paid!')
                        ->body("Payroll {$record->payroll_number} has been marked as paid.")
                        ->success()
                        ->send();

                    $this->redirect(PayrollResource::getUrl('view', ['record' => $record]));
                }),

            Action::make('cancel')
                ->label('Cancel Payroll')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel Payroll')
                ->modalDescription('Are you sure you want to cancel this payroll? This action cannot be undone.')
                ->hidden(fn () => ! $this->record->canBeCancelled())
                ->action(function () {
                    $record = $this->record;
                    $oldStatus = $record->status->value;

                    $record->update([
                        'status' => PayrollStatus::Cancelled,
                    ]);

                    AuditLog::create([
                        'user_id' => Auth::id(),
                        'user_name' => Auth::user()?->name,
                        'action' => 'cancelled',
                        'auditable_type' => $record->getMorphClass(),
                        'auditable_id' => $record->getKey(),
                        'auditable_label' => "Payroll {$record->payroll_number}",
                        'old_values' => ['status' => $oldStatus],
                        'new_values' => ['status' => 'cancelled'],
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('Payroll Cancelled')
                        ->body("Payroll {$record->payroll_number} has been cancelled.")
                        ->success()
                        ->send();

                    $this->redirect(PayrollResource::getUrl('view', ['record' => $record]));
                }),

            EditAction::make()
                ->hidden(fn () => $this->record->status !== PayrollStatus::Draft),

            DeleteAction::make()
                ->hidden(fn () => $this->record->status !== PayrollStatus::Draft),
        ];
    }
}
