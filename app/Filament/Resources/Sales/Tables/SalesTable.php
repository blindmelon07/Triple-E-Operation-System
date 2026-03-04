<?php

namespace App\Filament\Resources\Sales\Tables;

use App\Models\Sale;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('date')->date(),
                TextColumn::make('sale_items_count')->counts('sale_items')->label('Items'),
                TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        default   => 'danger',
                    }),
                TextColumn::make('total')->money('Php')->summarize(Sum::make()),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Sale as Paid')
                    ->modalDescription(fn (Sale $record) => "Mark ₱" . number_format($record->total, 2) . " sale as fully paid?")
                    ->visible(fn (Sale $record): bool => $record->payment_term_days && $record->payment_status !== 'paid')
                    ->action(function (Sale $record): void {
                        $record->update([
                            'payment_status' => 'paid',
                            'amount_paid'    => $record->total,
                            'paid_date'      => now()->toDateString(),
                        ]);
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
