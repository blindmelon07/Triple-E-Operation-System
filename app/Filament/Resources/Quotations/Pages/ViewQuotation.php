<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Mail\QuotationApprovedMail;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewQuotation extends ViewRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () =>
                    $this->record->status === QuotationStatus::Pending->value &&
                    (auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('approve_quotation'))
                )
                ->action(function () {
                    $this->record->update(['status' => QuotationStatus::Approved->value]);

                    AuditLog::create([
                        'user_id'         => auth()->id(),
                        'user_name'       => auth()->user()?->name,
                        'action'          => 'approved',
                        'auditable_type'  => $this->record->getMorphClass(),
                        'auditable_id'    => $this->record->getKey(),
                        'auditable_label' => "Quotation {$this->record->quotation_number}",
                        'old_values'      => ['status' => QuotationStatus::Pending->value],
                        'new_values'      => ['status' => QuotationStatus::Approved->value],
                        'ip_address'      => request()->ip(),
                        'user_agent'      => request()->userAgent(),
                    ]);

                    if ($this->record->creator && $this->record->creator->email) {
                        $this->record->load(['customer']);
                        Mail::to($this->record->creator->email)->send(new QuotationApprovedMail($this->record));
                    }

                    Notification::make()
                        ->title('Quotation Approved')
                        ->body('The quotation has been approved successfully.')
                        ->success()
                        ->send();
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () =>
                    $this->record->status === QuotationStatus::Pending->value &&
                    (auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('approve_quotation'))
                )
                ->action(function () {
                    $this->record->update(['status' => QuotationStatus::Rejected->value]);

                    AuditLog::create([
                        'user_id'         => auth()->id(),
                        'user_name'       => auth()->user()?->name,
                        'action'          => 'rejected',
                        'auditable_type'  => $this->record->getMorphClass(),
                        'auditable_id'    => $this->record->getKey(),
                        'auditable_label' => "Quotation {$this->record->quotation_number}",
                        'old_values'      => ['status' => QuotationStatus::Pending->value],
                        'new_values'      => ['status' => QuotationStatus::Rejected->value],
                        'ip_address'      => request()->ip(),
                        'user_agent'      => request()->userAgent(),
                    ]);

                    Notification::make()
                        ->title('Quotation Rejected')
                        ->body('The quotation has been rejected.')
                        ->success()
                        ->send();
                }),
            Action::make('print')
                ->label('Print Quotation')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn () => route('pos.print-quotation', $this->record))
                ->openUrlInNewTab(),
            EditAction::make(),
        ];
    }
}
