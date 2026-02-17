<?php

namespace App\Filament\Resources\GovernmentContributions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GovernmentContributionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sss' => 'info',
                        'philhealth' => 'success',
                        'pagibig' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sss' => 'SSS',
                        'philhealth' => 'PhilHealth',
                        'pagibig' => 'Pag-IBIG',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('salary_from')
                    ->label('Salary From')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('salary_to')
                    ->label('Salary To')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('employee_share')
                    ->label('Employee Share')
                    ->money('PHP')
                    ->sortable(),

                TextColumn::make('employer_share')
                    ->label('Employer Share')
                    ->money('PHP')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'sss' => 'SSS',
                        'philhealth' => 'PhilHealth',
                        'pagibig' => 'Pag-IBIG',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('type')
            ->defaultSort('salary_from');
    }
}
