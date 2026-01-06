<?php

namespace App\Filament\Resources\MaintenanceRequests\Tables;

use App\Models\MaintenanceRecord;
use App\Models\MaintenanceRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class MaintenanceRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_number')
                    ->label('Request #')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('vehicle.plate_number')
                    ->label('Vehicle')
                    ->description(fn ($record) => $record->vehicle?->full_name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('maintenanceType.name')
                    ->label('Service Type')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('priority')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'gray',
                        'normal' => 'info',
                        'high' => 'warning',
                        'urgent' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'completed' => 'info',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('requestedBy.name')
                    ->label('Requested By')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('preferred_date')
                    ->label('Preferred Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('estimated_cost')
                    ->label('Est. Cost')
                    ->money('PHP')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending'),

                SelectFilter::make('priority')
                    ->label('Priority')
                    ->options([
                        'low' => 'Low',
                        'normal' => 'Normal',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ]),

                SelectFilter::make('vehicle_id')
                    ->label('Vehicle')
                    ->relationship('vehicle', 'plate_number')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('maintenance_type_id')
                    ->label('Service Type')
                    ->relationship('maintenanceType', 'name')
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->hidden(fn ($record) => $record->status !== 'pending'),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Request')
                    ->modalDescription('Are you sure you want to approve this maintenance request? A maintenance record will be created.')
                    ->hidden(fn ($record) => ! $record->canBeApproved())
                    ->action(function (MaintenanceRequest $record) {
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
                            ->title('Request Approved')
                            ->body("Maintenance record #{$maintenanceRecord->reference_number} has been created.")
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Request')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3)
                            ->placeholder('Please provide a reason for rejecting this request...'),
                    ])
                    ->hidden(fn ($record) => ! $record->canBeRejected())
                    ->action(function (MaintenanceRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'rejected_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Request Rejected')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            $approved = 0;
                            foreach ($records as $record) {
                                if ($record->canBeApproved()) {
                                    $record->update([
                                        'status' => 'approved',
                                        'approved_by' => Auth::id(),
                                        'approved_at' => now(),
                                    ]);

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
                                    $approved++;
                                }
                            }

                            Notification::make()
                                ->title("{$approved} Request(s) Approved")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
