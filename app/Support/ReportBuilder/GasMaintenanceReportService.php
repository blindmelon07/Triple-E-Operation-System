<?php

namespace App\Support\ReportBuilder;

use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds a per-vehicle summary of gas (fuel) and maintenance spend over an
 * optional date range — one row per vehicle with fuel cost/liters,
 * maintenance cost, and a combined total, plus a grand-total row.
 */
class GasMaintenanceReportService
{
    /**
     * @return array{
     *     rows: Collection<int, Vehicle>,
     *     totals: array{fuel_total: float, fuel_liters: float, maintenance_total: float, grand_total: float},
     * }
     */
    public function build(?string $dateFrom = null, ?string $dateTo = null, ?int $vehicleId = null): array
    {
        $vehicles = Vehicle::query()
            ->when($vehicleId, fn (Builder $q) => $q->where('id', $vehicleId))
            ->withSum(['fuelLogs as fuel_total' => fn (Builder $q) => $this->applyDateRange($q, 'fuel_date', $dateFrom, $dateTo)], 'cost')
            ->withSum(['fuelLogs as fuel_liters' => fn (Builder $q) => $this->applyDateRange($q, 'fuel_date', $dateFrom, $dateTo)], 'liters')
            ->withCount(['fuelLogs as fuel_count' => fn (Builder $q) => $this->applyDateRange($q, 'fuel_date', $dateFrom, $dateTo)])
            ->withSum(['maintenanceRecords as maintenance_total' => fn (Builder $q) => $this->applyDateRange($q, 'maintenance_date', $dateFrom, $dateTo)], 'cost')
            ->withCount(['maintenanceRecords as maintenance_count' => fn (Builder $q) => $this->applyDateRange($q, 'maintenance_date', $dateFrom, $dateTo)])
            ->orderBy('plate_number')
            ->get()
            ->filter(fn (Vehicle $v) => $v->fuel_count > 0 || $v->maintenance_count > 0)
            ->values();

        $vehicles->each(function (Vehicle $v) {
            $v->fuel_total = (float) ($v->fuel_total ?? 0);
            $v->fuel_liters = (float) ($v->fuel_liters ?? 0);
            $v->maintenance_total = (float) ($v->maintenance_total ?? 0);
            $v->grand_total = $v->fuel_total + $v->maintenance_total;
        });

        return [
            'rows' => $vehicles->sortByDesc('grand_total')->values(),
            'totals' => [
                'fuel_total' => (float) $vehicles->sum('fuel_total'),
                'fuel_liters' => (float) $vehicles->sum('fuel_liters'),
                'maintenance_total' => (float) $vehicles->sum('maintenance_total'),
                'grand_total' => (float) $vehicles->sum('grand_total'),
            ],
        ];
    }

    private function applyDateRange(Builder $query, string $column, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate($column, '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate($column, '<=', $to));
    }
}
