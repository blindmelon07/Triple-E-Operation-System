<?php

namespace App\Support\ReportBuilder;

use App\Models\MiscellaneousExpense;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds an itemized miscellaneous-expense ledger over an optional date range
 * — one row per expense (date, description, paid to, SI/DR/OR #, truck/unit,
 * amount), with a running cumulative total, ordered oldest to newest.
 */
class MiscellaneousReportService
{
    /**
     * @return array{
     *     rows: Collection<int, array{date: string, reference_number: string, description: string, payee: string, si_dr_number: string, truck: string, plate_number: string, amount: float, running_total: float}>,
     *     totals: array{count: int, amount: float},
     * }
     */
    public function build(?string $dateFrom = null, ?string $dateTo = null, ?int $vehicleId = null): array
    {
        $expenses = MiscellaneousExpense::query()
            ->with('vehicle')
            ->when($vehicleId, fn (Builder $q) => $q->where('vehicle_id', $vehicleId))
            ->when($dateFrom, fn (Builder $q) => $q->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $q) => $q->whereDate('expense_date', '<=', $dateTo))
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $runningTotal = 0.0;

        $rows = $expenses->map(function (MiscellaneousExpense $expense) use (&$runningTotal) {
            $amount = (float) $expense->amount;
            $runningTotal += $amount;

            return [
                'date' => optional($expense->expense_date)->format('n/j/Y') ?? '',
                'reference_number' => $expense->reference_number ?? '',
                'description' => $expense->description ?? '',
                'payee' => $expense->payee ?? '',
                'si_dr_number' => $expense->si_dr_number ?? '',
                'truck' => $expense->vehicle?->full_name ?? '',
                'plate_number' => $expense->vehicle?->plate_number ?? '',
                'amount' => $amount,
                'running_total' => $runningTotal,
            ];
        });

        return [
            'rows' => $rows,
            'totals' => [
                'count' => $expenses->count(),
                'amount' => $runningTotal,
            ],
        ];
    }
}
