<?php

namespace App\Filament\Resources\EmployeeCompensations\Tables;

use App\Enums\PayPeriodType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeCompensationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('daily_rate')
                    ->label('Daily Rate')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('pay_period')
                    ->label('Pay Period')
                    ->badge()
                    ->color(fn (PayPeriodType $state): string => $state->getColor())
                    ->formatStateUsing(fn (PayPeriodType $state): string => $state->getLabel())
                    ->sortable(),

                TextColumn::make('overtime_rate_multiplier')
                    ->label('OT Rate')
                    ->suffix('x')
                    ->sortable(),

                TextColumn::make('late_deduction_type')
                    ->label('Late Deduction')
                    ->formatStateUsing(fn (string $state): string => $state === 'per_minute' ? 'Per Minute' : 'Fixed')
                    ->sortable(),

                TextColumn::make('allowance')
                    ->label('Allowance')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('pay_period')
                    ->label('Pay Period')
                    ->options(
                        collect(PayPeriodType::cases())
                            ->mapWithKeys(fn ($p) => [$p->value => $p->getLabel()])
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
