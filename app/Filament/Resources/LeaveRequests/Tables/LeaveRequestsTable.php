<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use App\Enums\LeaveRequestStatus;
use App\Models\AuditLog;
use App\Models\LeaveRequest;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LeaveRequestsTable
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

                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('leaveType.name')
                    ->label('Leave Type')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('End')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('total_days')
                    ->label('Days')
                    ->suffix(' days')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (LeaveRequestStatus $state): string => $state->getColor())
                    ->formatStateUsing(fn (LeaveRequestStatus $state): string => $state->getLabel())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(
                        collect(LeaveRequestStatus::cases())
                            ->mapWithKeys(fn ($s) => [$s->value => $s->getLabel()])
                    )
                    ->default('pending'),

                SelectFilter::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name')
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->hidden(fn ($record) => $record->status !== LeaveRequestStatus::Pending),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Leave Request')
                    ->modalDescription('Are you sure you want to approve this leave request?')
                    ->hidden(fn ($record) => ! $record->canBeApproved())
                    ->action(function (LeaveRequest $record) {
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
                            ->title('Leave Request Approved')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Leave Request')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3)
                            ->placeholder('Please provide a reason for rejecting this request...'),
                    ])
                    ->hidden(fn ($record) => ! $record->canBeRejected())
                    ->action(function (LeaveRequest $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'rejected_at' => now(),
                        ]);

                        AuditLog::create([
                            'user_id' => Auth::id(),
                            'user_name' => Auth::user()?->name,
                            'action' => 'rejected',
                            'auditable_type' => $record->getMorphClass(),
                            'auditable_id' => $record->getKey(),
                            'auditable_label' => "Leave Request {$record->request_number}",
                            'old_values' => ['status' => 'pending'],
                            'new_values' => ['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']],
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ]);

                        Notification::make()
                            ->title('Leave Request Rejected')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
