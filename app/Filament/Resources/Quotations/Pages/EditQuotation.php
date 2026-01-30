<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Mail\QuotationApprovedMail;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $total = 0;
        if (!empty($data['quotation_items'])) {
            foreach ($data['quotation_items'] as $item) {
                $total += $item['price'] ?? 0;
            }
        }
        $data['total'] = $total;

        return $data;
    }

    protected function afterSave(): void
    {
        // Recalculate total after items are saved
        $this->record->refresh();
        $total = $this->record->quotation_items()->sum('price');
        $this->record->updateQuietly(['total' => $total]);
    }

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
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
