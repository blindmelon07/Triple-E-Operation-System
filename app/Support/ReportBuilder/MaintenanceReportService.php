<?php

namespace App\Support\ReportBuilder;

use App\Models\MaintenanceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds an itemized maintenance-expense ledger over an optional date
 * range — one row per maintenance record (date, supplier, SI/DR #, PO #,
 * amount), with a running cumulative total down the table, in entry order.
 * buildPerSupplier() returns the same records grouped under each supplier,
 * with a running total and subtotal per supplier.
 */
class MaintenanceReportService
{
    public const NO_SUPPLIER_LABEL = 'No Supplier';

    /**
     * @return array{
     *     rows: Collection<int, array{date: string, supplier: string, vehicle: string, si_number: string, po_number: string, amount: float, running_total: float}>,
     *     totals: array{amount: float},
     * }
     */
    public function build(?string $dateFrom = null, ?string $dateTo = null, ?int $vehicleId = null, ?int $supplierId = null): array
    {
        $records = $this->query($dateFrom, $dateTo, $vehicleId, $supplierId)
            ->orderBy('id')
            ->get();

        $runningTotal = 0.0;

        $rows = $records->map(function (MaintenanceRecord $record) use (&$runningTotal) {
            $amount = (float) $record->cost;
            $runningTotal += $amount;

            return [...$this->row($record), 'running_total' => $runningTotal];
        });

        return [
            'rows' => $rows,
            'totals' => [
                'amount' => $runningTotal,
            ],
        ];
    }

    /**
     * @return array{
     *     groups: array<int, array{supplier: string, count: int, subtotal: float, rows: array<int, array<string, mixed>>}>,
     *     totals: array{amount: float},
     * }
     */
    public function buildPerSupplier(?string $dateFrom = null, ?string $dateTo = null, ?int $vehicleId = null, ?int $supplierId = null): array
    {
        $records = $this->query($dateFrom, $dateTo, $vehicleId, $supplierId)
            ->orderBy('maintenance_date')
            ->orderBy('id')
            ->get();

        $groups = $records
            ->groupBy(fn (MaintenanceRecord $record) => $record->supplier?->name ?? self::NO_SUPPLIER_LABEL)
            // Alphabetical, with records that have no supplier listed last.
            ->sortBy(fn ($records, string $name) => [$name === self::NO_SUPPLIER_LABEL ? 1 : 0, strtolower($name)])
            ->map(function (Collection $records, string $name) {
                $runningTotal = 0.0;

                $rows = $records->map(function (MaintenanceRecord $record) use (&$runningTotal) {
                    $runningTotal += (float) $record->cost;

                    return [...$this->row($record), 'running_total' => $runningTotal];
                })->values()->all();

                return [
                    'supplier' => $name,
                    'count' => count($rows),
                    'subtotal' => $runningTotal,
                    'rows' => $rows,
                ];
            })
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'totals' => [
                'amount' => (float) array_sum(array_column($groups, 'subtotal')),
            ],
        ];
    }

    private function query(?string $dateFrom, ?string $dateTo, ?int $vehicleId, ?int $supplierId): Builder
    {
        return MaintenanceRecord::query()
            ->with(['supplier', 'vehicle'])
            ->when($vehicleId, fn (Builder $q) => $q->where('vehicle_id', $vehicleId))
            ->when($supplierId, fn (Builder $q) => $q->where('supplier_id', $supplierId))
            ->when($dateFrom, fn (Builder $q) => $q->whereDate('maintenance_date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $q) => $q->whereDate('maintenance_date', '<=', $dateTo));
    }

    /**
     * @return array{date: string, supplier: string, vehicle: string, si_number: string, po_number: string, amount: float}
     */
    private function row(MaintenanceRecord $record): array
    {
        return [
            'date' => optional($record->maintenance_date)->format('n/j/Y') ?? '',
            'supplier' => $record->supplier?->name ?? '',
            'vehicle' => $record->vehicle?->display_name ?? '',
            'si_number' => $record->si_number ?? '',
            'po_number' => $record->po_number ?? '',
            'amount' => (float) $record->cost,
        ];
    }
}
