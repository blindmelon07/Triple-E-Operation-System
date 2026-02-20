<?php

namespace App\Filament\Resources\Purchases\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')->label('Supplier')->searchable(),
                TextColumn::make('date')->date(),
                TextColumn::make('purchase_items_count')->counts('purchase_items')->label('Items'),
                TextColumn::make('total')->money('PHP')->label('Total'),
                TextColumn::make('receipt_status')
                    ->label('Receipt')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'success',
                        'partial'  => 'warning',
                        'pending'  => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'warning',
                        'unpaid'  => 'danger',
                        default   => 'gray',
                    }),
                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && $record->payment_status !== 'paid' ? 'danger' : null),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
