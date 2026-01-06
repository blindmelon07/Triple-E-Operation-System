<?php

namespace App\Filament\Resources\MaintenanceRequests\Pages;

use App\Filament\Resources\MaintenanceRequests\MaintenanceRequestResource;
use App\Models\MaintenanceRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewMaintenanceRequest extends ViewRecord
{
    protected static string $resource = MaintenanceRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Request')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Approve Maintenance Request')
                ->modalDescription('Are you sure you want to approve this request? A maintenance record will be created automatically.')
                ->hidden(fn () => ! $this->record->canBeApproved())
                ->action(function () {
                    $record = $this->record;

                    $record->update([
                        'status' => 'approved',
                        'approved_by' => Auth::id(),
                        'approved_at' => now(),
                    ]);

                    // Create a maintenance record
                    $maintenanceRecord = MaintenanceRecord::create([
                        'vehicle_id' => $record->vehicle_id,
                        'maintenance_type_id' => $record->maintenance_type_id,
                        'user_id' => Auth::id(),
                        'reference_number' => MaintenanceRecord::generateReferenceNumber(),
                        'maintenance_date' => $record->preferred_date ?? now(),
                        'mileage_at_service' => $record->current_mileage ?? $record->vehicle->current_mileage,
                        'cost' => $record->estimated_cost ?? 0,
                        'parts_cost' => 0,
                        'labor_cost' => 0,
                        'description' => "From Request #{$record->request_number}: {$record->description}",
                        'status' => 'scheduled',
                    ]);

                    $record->update(['maintenance_record_id' => $maintenanceRecord->id]);

                    Notification::make()
                        ->title('Request Approved!')
                        ->body("Maintenance record #{$maintenanceRecord->reference_number} has been created and scheduled.")
                        ->success()
                        ->send();

                    $this->redirect(MaintenanceRequestResource::getUrl('view', ['record' => $record]));
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

                    Notification::make()
                        ->title('Request Rejected')
                        ->body('The maintenance request has been rejected.')
                        ->success()
                        ->send();

                    $this->redirect(MaintenanceRequestResource::getUrl('view', ['record' => $this->record]));
                }),

            Action::make('view_record')
                ->label('View Service Record')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => route('filament.tos.resources.maintenance-records.view', ['record' => $this->record->maintenance_record_id]))
                ->hidden(fn () => ! $this->record->maintenance_record_id),

            EditAction::make()
                ->hidden(fn () => $this->record->status !== 'pending'),

            DeleteAction::make()
                ->hidden(fn () => $this->record->status !== 'pending'),
        ];
    }
}
