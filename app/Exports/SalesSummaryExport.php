<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesSummaryExport
{
    public function __construct(
        protected ?string $period = null,
        protected ?string $dateFrom = null,
        protected ?string $dateUntil = null,
    ) {}

    public function query(): Builder
    {
        $query = Sale::query()->with('customer');

        return $this->applyDateFilter($query, 'date');
    }

    protected function applyDateFilter(Builder $query, string $dateColumn): Builder
    {
        if ($this->dateFrom && $this->dateUntil) {
            return $query->whereDate($dateColumn, '>=', $this->dateFrom)
                ->whereDate($dateColumn, '<=', $this->dateUntil);
        }

        return match ($this->period) {
            'today'      => $query->whereDate($dateColumn, today()),
            'yesterday'  => $query->whereDate($dateColumn, today()->subDay()),
            'this_week'  => $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]),
            'last_week'  => $query->whereBetween($dateColumn, [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
            'this_month' => $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year),
            'last_month' => $query->whereMonth($dateColumn, now()->subMonth()->month)->whereYear($dateColumn, now()->subMonth()->year),
            'this_year'  => $query->whereYear($dateColumn, now()->year),
            default      => $query,
        };
    }

    public function getData(): Collection
    {
        $sales = $this->query()->orderBy('date')->get();

        $grouped = $sales->groupBy(fn (Sale $sale) => $sale->date?->format('Y-m-d'));

        $rows = $grouped->map(function (Collection $daySales, string $date) {
            return [
                'Date'        => $date,
                'Sales Count' => $daySales->count(),
                'Total'       => number_format($daySales->sum('total'), 2),
            ];
        })->values();

        // Append grand total row
        $rows->push([
            'Date'        => 'GRAND TOTAL',
            'Sales Count' => $sales->count(),
            'Total'       => number_format($sales->sum('total'), 2),
        ]);

        return $rows;
    }

    public function getHeaders(): array
    {
        return ['Date', 'Sales Count', 'Total'];
    }

    public function getFilename(): string
    {
        $suffix = $this->period ?? 'custom';

        return "sales-summary-{$suffix}-" . now()->format('Y-m-d-His') . '.csv';
    }
}
