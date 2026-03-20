<?php

namespace App\Filament\Resources\Sales\Pages;

use App\Filament\Resources\Sales\SaleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordPayment')
                ->label('Record Payment')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => $this->record->payment_status !== 'paid' && auth()->user()->hasPermissionTo('RecordPaymentSale'))
                ->fillForm(fn () => [
                    'amount_paid'    => $this->record->balance,
                    'payment_method' => $this->record->payment_method ?? 'cash',
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
                            'gcash'  => 'GCash',
                            'bank'   => 'Bank Transfer',
                            'check'  => 'Check',
                            'credit' => 'Credit',
                        ])
                        ->required(),
                    DatePicker::make('paid_date')
                        ->label('Payment Date')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $sale = $this->record;
                    $newAmountPaid = (float) $sale->amount_paid + (float) $data['amount_paid'];

                    $sale->amount_paid     = $newAmountPaid;
                    $sale->payment_method  = $data['payment_method'];
                    $sale->paid_date       = $data['paid_date'];
                    $sale->payment_status  = $newAmountPaid >= (float) $sale->total ? 'paid' : 'partial';
                    $sale->save();

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
