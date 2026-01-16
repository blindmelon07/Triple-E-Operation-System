<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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
                ->visible(fn () => $this->record->status === QuotationStatus::Pending->value)
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
                ->visible(fn () => $this->record->status === QuotationStatus::Pending->value)
                ->action(function () {
                    $this->record->update(['status' => QuotationStatus::Rejected->value]);
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
