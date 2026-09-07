<?php

namespace App\Filament\Resources\Purchases\Pages;

use App\Filament\Resources\Purchases\PurchaseResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchase extends EditRecord
{
    protected static string $resource = PurchaseResource::class;

    // Total is derived from the line items, so it has to be computed after
    // Filament has actually persisted the purchase_items relationship
    // (saveRelationships() runs after handleRecordUpdate) — computing it
    // from the raw form $data beforehand is fragile and can silently save 0
    // even when the items themselves saved fine (e.g. editing a purchase
    // just to fill in SI #/P.O # without touching the items repeater).
    protected function afterSave(): void
    {
        $this->recalculateTotal();
    }

    private function recalculateTotal(): void
    {
        $total = $this->record->purchase_items()
            ->get()
            ->sum(fn ($item) => (float) $item->price * (float) $item->quantity);

        $this->record->update(['total' => $total]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => $this->record->payment_status !== 'paid' && auth()->user()->hasPermissionTo('RecordPaymentPurchase'))
                ->fillForm(fn () => [
                    'amount_paid'    => $this->record->balance,
                    'payment_method' => 'cash',
                    'paid_date'      => now()->format('Y-m-d'),
                ])
                ->form([
                    TextInput::make('amount_paid')
                        ->label('Amount to Pay')
                        ->numeric()
                        ->prefix('₱')
                        ->required(),
                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'cash'   => 'Cash',
                            'bank'   => 'Bank Transfer',
                            'check'  => 'Check',
                            'online' => 'Online Payment',
                        ])
                        ->required(),
                    DatePicker::make('paid_date')
                        ->label('Payment Date')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $purchase = $this->record;
                    $newAmountPaid = (float) $purchase->amount_paid + (float) $data['amount_paid'];

                    $purchase->amount_paid    = $newAmountPaid;
                    $purchase->paid_date      = $data['paid_date'];
                    $purchase->payment_status = $newAmountPaid >= (float) $purchase->total ? 'paid' : 'partial';
                    $purchase->save();

                    $purchase->purchasePayments()->create([
                        'amount' => $data['amount_paid'],
                        'payment_method' => $data['payment_method'],
                        'paid_date' => $data['paid_date'],
                        'recorded_by_id' => auth()->id(),
                        'balance_after' => $purchase->balance,
                    ]);

                    Notification::make()
                        ->title('Payment recorded successfully')
                        ->success()
                        ->send();

                    $this->refreshFormData(['payment_status', 'amount_paid', 'paid_date']);
                }),
            DeleteAction::make(),
        ];
    }
}
