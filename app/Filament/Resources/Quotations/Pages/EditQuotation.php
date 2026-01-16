<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

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
