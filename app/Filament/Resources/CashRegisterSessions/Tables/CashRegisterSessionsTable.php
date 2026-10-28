<?php

namespace App\Filament\Resources\CashRegisterSessions\Tables;

use App\Enums\CashRegisterStatus;
use App\Filament\Resources\CashRegisterSessions\ClosingCorrectionActions;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashRegisterSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Cashier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('opened_at')
                    ->label('Opened')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->label('Closed')
                    ->dateTime('M d, Y h:i A')
                    ->sortable()
                    ->placeholder('Still Open'),
                TextColumn::make('opening_amount')
                    ->label('Opening')
                    ->money('Php')
                    ->sortable(),
                TextColumn::make('closing_amount')
                    ->label('Closing')
                    ->money('Php')
                    ->placeholder('-'),
                TextColumn::make('expected_amount')
                    ->label('Expected')
                    ->money('Php')
                    ->placeholder('-'),
                TextColumn::make('discrepancy')
                    ->label('Discrepancy')
                    ->money('Php')
                    ->placeholder('-')
                    ->color(fn ($record) => match (true) {
                        $record->discrepancy === null => null,
                        (float) $record->discrepancy < 0 => 'danger',
                        (float) $record->discrepancy > 0 => 'warning',
                        default => 'success',
                    }),
                // Counts pending requests only, so the badge clears once reviewed.
                TextColumn::make('closing_corrections_count')
                    ->label('Correction')
                    ->counts(['closingCorrections' => fn ($q) => $q->pending()])
                    ->formatStateUsing(fn ($state) => $state > 0 ? 'Pending' : '-')
                    ->badge(fn ($state) => $state > 0)
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                TextColumn::make('overrideApprover.name')
                    ->label('₱0 Close Approved By')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('total_sales')
                    ->label('Total Sales')
                    ->money('Php')
                    ->summarize(Sum::make()),
                TextColumn::make('total_transactions')
                    ->label('Transactions')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (CashRegisterStatus $state) => $state->getLabel())
                    ->color(fn (CashRegisterStatus $state) => $state->getColor()),
            ])
            ->defaultSort('opened_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(CashRegisterStatus::class),
                SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('Cashier'),
                Filter::make('pending_correction')
                    ->label('Pending closing correction')
                    ->query(fn ($query) => $query->whereHas('closingCorrections', fn ($q) => $q->pending())),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(ClosingCorrectionActions::all()),
            ])
            ->toolbarActions([]);
    }
}
