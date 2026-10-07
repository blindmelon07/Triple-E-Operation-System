<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Mail\CustomerStatementMail;
use App\Services\ReportExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_term_days')
                    ->label('Payment Terms')
                    ->formatStateUsing(fn ($state) => $state === 0 ? 'COD' : "Net {$state}")
                    ->badge()
                    ->color(fn ($state) => $state === 0 ? 'success' : 'info')
                    ->sortable(),
                TextColumn::make('contact_person')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sales_count')
                    ->counts('sales')
                    ->label('Total Orders')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('generate_soa')
                    ->label('Statement of Account')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('gray')
                    ->modalHeading('Generate Statement of Account')
                    ->modalSubmitActionLabel('Generate PDF')
                    ->schema([
                        DatePicker::make('soa_billing_date')
                            ->label('Billing Date')
                            ->native(false)
                            ->default(now())
                            ->required(),
                        TextInput::make('soa_number')
                            ->label('SOA No.')
                            ->maxLength(50)
                            ->default(fn ($record) => $record->suggestSoaNumber())
                            ->helperText('Saved to the customer and shown on the SOA Summary report.'),
                        DatePicker::make('date_from')
                            ->label('From')
                            ->native(false),
                        DatePicker::make('date_to')
                            ->label('To')
                            ->native(false)
                            ->afterOrEqual('date_from'),
                        Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'paid'   => 'Paid only',
                                'unpaid' => 'Unpaid only',
                            ])
                            ->placeholder('All invoices')
                            ->native(false),
                        Toggle::make('email_to_customer')
                            ->label('Also email this statement to the customer')
                            ->live()
                            ->default(false),
                        TextInput::make('email')
                            ->label('Send to')
                            ->email()
                            ->required()
                            ->visible(fn ($get) => $get('email_to_customer'))
                            ->default(fn ($record) => $record->email),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'soa_number'       => $data['soa_number'] ?: null,
                            'soa_billing_date' => $data['soa_billing_date'],
                        ]);

                        if ($data['email_to_customer'] ?? false) {
                            $pdf = (new ReportExportService)->buildCustomerStatementPdf(
                                $record,
                                $data['date_from'],
                                $data['date_to'],
                                $data['payment_status'],
                            );

                            Mail::to($data['email'])->send(new CustomerStatementMail(
                                $record,
                                $pdf,
                                $data['date_from'],
                                $data['date_to'],
                            ));

                            Notification::make()
                                ->title('Statement emailed to '.$data['email'])
                                ->success()
                                ->send();
                        }

                        return redirect(route('customer-statement.export-pdf', [
                            'customer'       => $record,
                            'date_from'      => $data['date_from'],
                            'date_to'        => $data['date_to'],
                            'payment_status' => $data['payment_status'],
                        ]));
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
