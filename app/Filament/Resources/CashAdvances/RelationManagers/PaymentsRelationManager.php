<?php

namespace App\Filament\Resources\CashAdvances\RelationManagers;

use App\Enums\PayrollStatus;
use App\Models\CashAdvance;
use App\Models\CashAdvancePayment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Repayment history for one advance. Payroll deductions show up here
 * automatically (read-only — change them by editing/cancelling the
 * payroll); manual cash repayments can be added, edited and removed.
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Repayments';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        /** @var CashAdvance $advance */
        $advance = $this->getOwnerRecord();

        return $schema
            ->components([
                DatePicker::make('payment_date')
                    ->label('Date')
                    ->required()
                    ->default(now())
                    ->maxDate(now()),

                TextInput::make('amount')
                    ->label('Amount')
                    ->required()
                    ->numeric()
                    ->prefix('₱')
                    ->minValue(0.01)
                    // Balance plus this row's own amount when editing, so an
                    // existing repayment can be re-saved unchanged.
                    ->maxValue(fn (?CashAdvancePayment $record) => round($advance->balance() + (float) ($record?->amount ?? 0), 2)),

                TextInput::make('notes')
                    ->label('Notes')
                    ->default('Cash repayment')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('payrollItem.payroll', 'user'))
            ->columns([
                TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('source')
                    ->label('Source')
                    ->state(function (CashAdvancePayment $record): string {
                        $payroll = $record->payrollItem?->payroll;

                        return $payroll ? "Payroll {$payroll->payroll_number}" : 'Manual';
                    })
                    ->badge()
                    ->color(fn (CashAdvancePayment $record) => $record->isPayrollDeduction() ? 'info' : 'gray')
                    ->description(fn (CashAdvancePayment $record) => $record->payrollItem?->payroll?->status === PayrollStatus::Cancelled
                        ? 'Payroll cancelled — not counted'
                        : null),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->alignEnd(),

                TextColumn::make('notes')
                    ->label('Notes'),

                TextColumn::make('user.name')
                    ->label('Recorded By')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Record Repayment')
                    ->hidden(fn () => $this->getOwnerRecord()->balance() <= 0)
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = Auth::id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn (CashAdvancePayment $record) => $record->isPayrollDeduction()),
                DeleteAction::make()
                    ->hidden(fn (CashAdvancePayment $record) => $record->isPayrollDeduction()),
            ])
            ->defaultSort('payment_date', 'desc');
    }
}
