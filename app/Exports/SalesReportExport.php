<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SalesReportExport
{
    public function __construct(
        protected ?string $period = null,
        protected ?string $dateFrom = null,
        protected ?string $dateUntil = null,
    ) {}

    public function query(): Builder
    {
        $query = Sale::query()->with(['customer', 'sale_items']);

        return $this->applyDateFilter($query, 'date');
    }

    protected function applyDateFilter(Builder $query, string $dateColumn): Builder
    {
        if ($this->dateFrom && $this->dateUntil) {
            return $query->whereDate($dateColumn, '>=', $this->dateFrom)
                ->whereDate($dateColumn, '<=', $this->dateUntil);
        }

        return match ($this->period) {
            'today' => $query->whereDate($dateColumn, today()),
            'yesterday' => $query->whereDate($dateColumn, today()->subDay()),
            'this_week' => $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]),
            'last_week' => $query->whereBetween($dateColumn, [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
            'this_month' => $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year),
            'last_month' => $query->whereMonth($dateColumn, now()->subMonth()->month)->whereYear($dateColumn, now()->subMonth()->year),
            'this_year' => $query->whereYear($dateColumn, now()->year),
            default => $query,
        };
    }

    public function getData(): Collection
    {
        return $this->query()->get()->map(function (Sale $sale) {
            return [
                'Date' => $sale->date?->format('Y-m-d H:i'),
                'Customer' => $sale->customer?->name ?? 'Walk-in',
                'Items Count' => $sale->sale_items->count(),
                'Total' => number_format($sale->total, 2),
            ];
        });
    }

    public function getHeaders(): array
    {
        return ['Date', 'Customer', 'Items Count', 'Total'];
    }

    public function getFilename(): string
    {
        $suffix = $this->period ?? 'custom';

        return "sales-report-{$suffix}-".now()->format('Y-m-d-His').'.csv';
    }
}
