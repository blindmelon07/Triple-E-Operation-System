<?php

namespace App\Filament\Resources\ExpenseCategories\Tables;

use App\Enums\ExpenseReportGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ExpenseCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('report_group')
                    ->label('Report Group')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof ExpenseReportGroup ? $state->label() : $state),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('expenses_count')
                    ->label('Expenses')
                    ->counts('expenses')
                    ->sortable(),

                TextColumn::make('expenses_sum_amount')
                    ->label('Total Amount')
                    ->sum('expenses', 'amount')
                    ->money('PHP')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Categories')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
                SelectFilter::make('report_group')
                    ->label('Report Group')
                    ->options(collect(ExpenseReportGroup::cases())->mapWithKeys(
                        fn (ExpenseReportGroup $case) => [$case->value => $case->label()]
                    )),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }
}
