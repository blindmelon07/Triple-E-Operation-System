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

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $total = 0;
        if (!empty($data['purchase_items'])) {
            foreach ($data['purchase_items'] as $item) {
                $total += ($item['price'] ?? 0) * ($item['quantity_received'] ?? 0);
            }
        }
        $data['total'] = $total;
        return parent::handleRecordUpdate($record, $data);
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
