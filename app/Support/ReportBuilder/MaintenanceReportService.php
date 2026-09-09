<?php

namespace App\Support\ReportBuilder;

use App\Models\MaintenanceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds an itemized maintenance-expense ledger over an optional date
 * range — one row per maintenance record (date, supplier, SI/DR #, PO #,
 * amount), with a running cumulative total down the table, in entry order.
 */
class MaintenanceReportService
{
    /**
     * @return array{
     *     rows: Collection<int, array{date: string, supplier: string, si_number: string, po_number: string, amount: float, running_total: float}>,
     *     totals: array{amount: float},
     * }
     */
    public function build(?string $dateFrom = null, ?string $dateTo = null, ?int $vehicleId = null): array
    {
        $records = MaintenanceRecord::query()
            ->with('supplier')
            ->when($vehicleId, fn (Builder $q) => $q->where('vehicle_id', $vehicleId))
            ->when($dateFrom, fn (Builder $q) => $q->whereDate('maintenance_date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $q) => $q->whereDate('maintenance_date', '<=', $dateTo))
            ->orderBy('id')
            ->get();

        $runningTotal = 0.0;

        $rows = $records->map(function (MaintenanceRecord $record) use (&$runningTotal) {
            $amount = (float) $record->cost;
            $runningTotal += $amount;

            return [
                'date' => optional($record->maintenance_date)->format('n/j/Y') ?? '',
                'supplier' => $record->supplier?->name ?? '',
                'si_number' => $record->si_number ?? '',
                'po_number' => $record->po_number ?? '',
                'amount' => $amount,
                'running_total' => $runningTotal,
            ];
        });

        return [
            'rows' => $rows,
            'totals' => [
                'amount' => $runningTotal,
            ],
        ];
    }
}
