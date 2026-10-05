<?php

namespace App\Filament\Resources\CashAdvances\Schemas;

use App\Models\CashAdvance;
use App\Models\Employee;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CashAdvanceForm
{
    public static function configure(Schema $schema): Schema
    {
        // Re-derive the per-payroll deduction whenever employee, type or
        // amount changes: regular = whole amount once, emergency = equal
        // installments over the allowed cut-offs.
        $recalculate = function (Get $get, Set $set): void {
            $amount = (float) $get('amount');

            if ($amount > 0) {
                $set('deduction_per_payroll', CashAdvance::installmentFor(
                    $amount,
                    $get('type') ?? CashAdvance::TYPE_REGULAR,
                    $get('employee_id') ? (int) $get('employee_id') : null,
                ));
            }
        };

        $peso = fn (float $value): string => '₱'.number_format($value, 2);

        return $schema
            ->components([
                Section::make('Cash Advance')
                    ->schema([
                        TextInput::make('reference_number')
                            ->label('Reference Number')
                            ->default(fn () => CashAdvance::generateReferenceNumber())
                            ->disabled()
                            ->dehydrated(),

                        DatePicker::make('date_granted')
                            ->label('Date Granted')
                            ->required()
                            ->default(now())
                            ->maxDate(now()),

                        Select::make('employee_id')
                            ->label('Employee')
                            ->options(fn () => Employee::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->getOptionLabelUsing(fn ($value) => Employee::find($value)?->name)
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated($recalculate),

                        Select::make('type')
                            ->label('Type')
                            ->options(CashAdvance::typeOptions())
                            ->default(CashAdvance::TYPE_REGULAR)
                            ->required()
                            ->live()
                            ->afterStateUpdated($recalculate),

                        Placeholder::make('limits')
                            ->label('Limit for this employee')
                            ->columnSpanFull()
                            ->content(function (Get $get) use ($peso): string {
                                $employeeId = $get('employee_id') ? (int) $get('employee_id') : null;

                                if (! $employeeId) {
                                    return 'Select an employee to see their limit.';
                                }

                                $salary = CashAdvance::salaryPerCutoff($employeeId);

                                if ($salary === null) {
                                    return 'No compensation is set for this employee, so the limit cannot be checked. Add one under Employee Compensations.';
                                }

                                $type = $get('type') ?? CashAdvance::TYPE_REGULAR;
                                $cutoffs = CashAdvance::cutoffsFor($type, $employeeId);

                                return $type === CashAdvance::TYPE_EMERGENCY
                                    ? "Salary per cut-off: {$peso($salary)}. Emergency: up to {$peso($salary * $cutoffs)}, paid in {$cutoffs} equal cut-offs (max ".CashAdvance::EMERGENCY_MONTHS.' months).'
                                    : "Salary per cut-off: {$peso($salary)}. Regular: up to {$peso($salary)}, deducted in full on the next payroll.";
                            }),

                        TextInput::make('amount')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            // Can't shrink an advance below what has already
                            // been repaid — that would leave a negative balance.
                            ->minValue(fn (?CashAdvance $record) => max(0.01, $record?->totalPaid() ?? 0))
                            ->live(onBlur: true)
                            ->afterStateUpdated($recalculate)
                            ->rule(fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get, $peso): void {
                                $employeeId = $get('employee_id') ? (int) $get('employee_id') : null;
                                $type = $get('type') ?? CashAdvance::TYPE_REGULAR;
                                $max = CashAdvance::maxAmountFor($type, $employeeId);

                                if ($max !== null && (float) $value > $max) {
                                    $fail($type === CashAdvance::TYPE_EMERGENCY
                                        ? "Emergency CA can't exceed {$peso($max)} (one cut-off's salary × ".CashAdvance::cutoffsFor($type, $employeeId).' cut-offs).'
                                        : "Regular CA can't exceed one cut-off's salary ({$peso($max)}). Use Emergency for larger amounts.");
                                }
                            }),

                        TextInput::make('deduction_per_payroll')
                            ->label('Deduction per Payroll')
                            ->helperText('Calculated automatically from the type and amount. Deducted on every generated payroll until fully paid. Set to 0 to pause deductions.')
                            ->required()
                            ->numeric()
                            ->prefix('₱')
                            ->default(0)
                            ->minValue(0)
                            ->rule(fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get, $peso): void {
                                $salary = CashAdvance::salaryPerCutoff($get('employee_id') ? (int) $get('employee_id') : null);

                                if ($salary !== null && (float) $value > $salary) {
                                    $fail("Can't deduct more than one cut-off's salary ({$peso($salary)}).");
                                }
                            }),

                        TextInput::make('purpose')
                            ->label('Purpose')
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
