<?php

namespace App\Filament\Resources\Payrolls\RelationManagers;

use App\Enums\PayrollStatus;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PayrollItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'payrollItems';

    protected static ?string $title = 'Payroll Items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('overtime_hours')
                    ->label('Overtime Hours')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),

                TextInput::make('overtime_pay')
                    ->label('Overtime Pay')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0),

                TextInput::make('bonus')
                    ->label('Bonus')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0),

                Textarea::make('bonus_description')
                    ->label('Bonus Description')
                    ->rows(2),

                TextInput::make('other_deduction')
                    ->label('Other Deduction')
                    ->numeric()
                    ->default(0)
                    ->prefix('₱')
                    ->minValue(0),

                Textarea::make('other_deduction_description')
                    ->label('Other Deduction Description')
                    ->rows(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.name')
                    ->label('Employee')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('days_worked')
                    ->label('Days Worked')
                    ->alignEnd()
                    ->sortable(),

                TextColumn::make('days_absent')
                    ->label('Absent')
                    ->alignEnd()
                    ->color(fn ($state) => $state > 0 ? 'danger' : null)
                    ->sortable(),

                TextColumn::make('gross_pay')
                    ->label('Gross')
                    ->money('PHP')
                    ->alignEnd()
                    ->sortable()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),

                TextColumn::make('total_deductions')
                    ->label('Deductions')
                    ->money('PHP')
                    ->alignEnd()
                    ->color('danger')
                    ->summarize(Sum::make()->money('PHP')->label('Total')),

                TextColumn::make('net_pay')
                    ->label('Net Pay')
                    ->money('PHP')
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success')
                    ->sortable()
                    ->summarize(Sum::make()->money('PHP')->label('Total')),

                // Everything below is the breakdown behind Gross/Deductions —
                // hidden by default so the table stays scannable; toggle
                // them on (column-toggle button, top right) when you need
                // to audit a specific figure.
                TextColumn::make('daily_rate')
                    ->label('Daily Rate')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('overtime_hours')
                    ->label('OT Hrs')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('overtime_pay')
                    ->label('OT Pay')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bonus')
                    ->label('Bonus')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('allowance')
                    ->label('Allowance')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('late_deduction')
                    ->label('Late Ded.')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sss_deduction')
                    ->label('SSS')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('philhealth_deduction')
                    ->label('PhilHealth')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('pagibig_deduction')
                    ->label('Pag-IBIG')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('other_deduction')
                    ->label('Other Ded.')
                    ->money('PHP')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn () => $this->getOwnerRecord()->status !== PayrollStatus::Draft)
                    ->after(function ($record) {
                        // Recalculate gross, total deductions, and net pay
                        $grossPay = ((float) $record->daily_rate * (float) $record->days_worked)
                            + (float) $record->overtime_pay
                            + (float) $record->bonus
                            + (float) $record->allowance;

                        $totalDeductions = (float) $record->late_deduction
                            + (float) $record->sss_deduction
                            + (float) $record->philhealth_deduction
                            + (float) $record->pagibig_deduction
                            + (float) $record->other_deduction;

                        $record->update([
                            'gross_pay' => $grossPay,
                            'total_deductions' => $totalDeductions,
                            'net_pay' => $grossPay - $totalDeductions,
                        ]);

                        // Recalculate payroll totals
                        $this->getOwnerRecord()->recalculateTotals();
                    }),
            ])
            ->defaultSort('employee.name');
    }
}
