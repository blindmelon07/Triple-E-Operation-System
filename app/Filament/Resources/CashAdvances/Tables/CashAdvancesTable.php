<?php

namespace App\Filament\Resources\CashAdvances\Tables;

use App\Models\CashAdvance;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashAdvancesTable
{
    public static function configure(Table $table): Table
    {
        // paid_amount comes from CashAdvanceResource::getEloquentQuery().
        $balance = fn (CashAdvance $record): float => round((float) $record->amount - (float) $record->paid_amount, 2);

        return $table
            ->columns([
                TextColumn::make('reference_number')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date_granted')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('purpose')
                    ->label('Purpose')
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('deduction_per_payroll')
                    ->label('Per Payroll')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(),

                TextColumn::make('paid_amount')
                    ->label('Paid')
                    ->money('PHP')
                    ->alignEnd()
                    ->default(0),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->state($balance)
                    ->money('PHP')
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn (CashAdvance $record) => $balance($record) > 0 ? 'danger' : 'success'),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (CashAdvance $record) => $balance($record) > 0 ? 'Outstanding' : 'Fully Paid')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Outstanding' ? 'warning' : 'success'),

                TextColumn::make('user.name')
                    ->label('Recorded By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label('Employee')
                    ->relationship('employee', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'outstanding' => 'Outstanding',
                        'paid' => 'Fully Paid',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // Same "effective payments" rule as the balance column,
                        // as a correlated subquery so it works without HAVING.
                        $paid = '(SELECT COALESCE(SUM(p.amount), 0) FROM cash_advance_payments p'
                            .' LEFT JOIN payroll_items pi ON pi.id = p.payroll_item_id'
                            .' LEFT JOIN payrolls pr ON pr.id = pi.payroll_id'
                            ." WHERE p.cash_advance_id = cash_advances.id AND (p.payroll_item_id IS NULL OR pr.status != 'cancelled'))";

                        return match ($data['value'] ?? null) {
                            'outstanding' => $query->whereRaw("cash_advances.amount - {$paid} > 0"),
                            'paid' => $query->whereRaw("cash_advances.amount - {$paid} <= 0"),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->defaultSort('date_granted', 'desc');
    }
}
