<?php

namespace App\Filament\Resources\Quotations\Tables;

use App\Enums\QuotationStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuotationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('quotation_number')
                    ->label('Quotation #')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('valid_until')
                    ->label('Valid Until')
                    ->date()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('quotation_items_count')
                    ->counts('quotation_items')
                    ->label('Items'),
                \Filament\Tables\Columns\TextColumn::make('total')
                    ->money('Php')
                    ->sortable()
                    ->summarize(Sum::make()),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => QuotationStatus::tryFrom($state)?->getLabel() ?? $state)
                    ->color(fn ($state) => QuotationStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        QuotationStatus::Pending->value => QuotationStatus::Pending->getLabel(),
                        QuotationStatus::Approved->value => QuotationStatus::Approved->getLabel(),
                        QuotationStatus::Rejected->value => QuotationStatus::Rejected->getLabel(),
                        QuotationStatus::ConvertedToSale->value => QuotationStatus::ConvertedToSale->getLabel(),
                        QuotationStatus::Expired->value => QuotationStatus::Expired->getLabel(),
                    ])
                    ->label('Status'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        $record->status === QuotationStatus::Pending->value &&
                        (auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('approve_quotation'))
                    )
                    ->action(function ($record) {
                        $record->update(['status' => QuotationStatus::Approved->value]);
                        Notification::make()
                            ->title('Quotation Approved')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) =>
                        $record->status === QuotationStatus::Pending->value &&
                        (auth()->user()->hasRole('super_admin') || auth()->user()->hasPermissionTo('approve_quotation'))
                    )
                    ->action(function ($record) {
                        $record->update(['status' => QuotationStatus::Rejected->value]);
                        Notification::make()
                            ->title('Quotation Rejected')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
