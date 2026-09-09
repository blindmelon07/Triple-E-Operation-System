<?php

namespace App\Support\ReportBuilder;

use App\Models\FuelLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds an itemized gas-expense ledger over an optional date range — one
 * row per fill-up (date, truck/unit, SI/DR #, gas station, liters, price
 * per liter, amount), with a running cumulative total down the table,
 * ordered oldest to newest.
 */
class GasReportService
{
    /**
     * @return array{
     *     rows: Collection<int, array{date: string, truck: string, plate_number: string, si_dr_number: string, fuel_station: string, liters: float, unit_price: float, amount: float, running_total: float}>,
     *     totals: array{liters: float, amount: float},
     * }
     */
    public function build(?string $dateFrom = null, ?string $dateTo = null, ?int $vehicleId = null): array
    {
        $logs = FuelLog::query()
            ->with('vehicle')
            ->when($vehicleId, fn (Builder $q) => $q->where('vehicle_id', $vehicleId))
            ->when($dateFrom, fn (Builder $q) => $q->whereDate('fuel_date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $q) => $q->whereDate('fuel_date', '<=', $dateTo))
            ->orderBy('fuel_date')
            ->orderBy('id')
            ->get();

        $runningTotal = 0.0;

        $rows = $logs->map(function (FuelLog $log) use (&$runningTotal) {
            $amount = (float) $log->cost;
            $runningTotal += $amount;

            return [
                'date' => optional($log->fuel_date)->format('n/j/Y') ?? '',
                'truck' => $log->vehicle?->full_name ?? '',
                'plate_number' => $log->vehicle?->plate_number ?? '',
                'si_dr_number' => $log->si_dr_number ?? '',
                'fuel_station' => $log->fuel_station ?? '',
                'liters' => (float) $log->liters,
                'unit_price' => (float) $log->price_per_liter,
                'amount' => $amount,
                'running_total' => $runningTotal,
            ];
        });

        return [
            'rows' => $rows,
            'totals' => [
                'liters' => (float) $logs->sum('liters'),
                'amount' => $runningTotal,
            ],
        ];
    }
}
