<?php

namespace App\Exports;

use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InventoryReportExport
{
    public function __construct(
        protected ?string $period = null,
        protected ?string $dateFrom = null,
        protected ?string $dateUntil = null,
        protected ?string $type = null,
        protected bool $summary = false,
    ) {}

    public function query(): Builder
    {
        $query = InventoryMovement::query()->with(['product']);

        $query = $this->applyDateFilter($query, 'created_at');

        return $query->when(
            $this->type,
            fn (Builder $query, string $type): Builder => $query->where('type', $type),
        );
    }

    protected function applyDateFilter(Builder $query, string $dateColumn): Builder
    {
        if ($this->dateFrom && $this->dateUntil) {
            return $query->whereDate($dateColumn, '>=', $this->dateFrom)
                ->whereDate($dateColumn, '<=', $this->dateUntil);
        }

        return static::applyPeriod($query, $dateColumn, $this->period);
    }

    public static function periodOptions(): array
    {
        return [
            'today' => 'Today (Daily)',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week (Weekly)',
            'last_week' => 'Last Week',
            'this_month' => 'This Month (Monthly)',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
        ];
    }

    public static function applyPeriod(Builder $query, string $dateColumn, ?string $period): Builder
    {
        return match ($period) {
            'today' => $query->whereDate($dateColumn, today()),
            'yesterday' => $query->whereDate($dateColumn, today()->subDay()),
            'this_week' => $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]),
            'last_week' => $query->whereBetween($dateColumn, [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
            'this_month' => $query->whereMonth($dateColumn, now()->month)->whereYear($dateColumn, now()->year),
            'last_month' => $query->whereMonth($dateColumn, now()->subMonthNoOverflow()->month)->whereYear($dateColumn, now()->subMonthNoOverflow()->year),
            'this_year' => $query->whereYear($dateColumn, now()->year),
            default => $query,
        };
    }

    public function getData(): Collection
    {
        if ($this->summary) {
            return $this->getSummaryData();
        }

        return $this->query()->get()->map(function (InventoryMovement $movement) {
            return [
                'Date' => $movement->created_at?->format('Y-m-d H:i'),
                'Product' => $movement->product?->name,
                'Type' => ucfirst($movement->type),
                'Quantity' => $movement->quantity,
                'Reason' => $movement->reason,
                'Notes' => $movement->notes,
            ];
        });
    }

    /**
     * One row per product with the total quantity moved in and out.
     */
    protected function getSummaryData(): Collection
    {
        return $this->query()
            ->get()
            ->groupBy('product_id')
            ->map(function (Collection $movements) {
                $totalIn = $movements->where('type', 'in')->sum('quantity');
                $totalOut = $movements->where('type', 'out')->sum('quantity');

                return [
                    'Product' => $movements->first()->product?->name,
                    'Total In' => $totalIn,
                    'Total Out' => $totalOut,
                    'Net (In - Out)' => $totalIn - $totalOut,
                    'No. of Movements' => $movements->count(),
                ];
            })
            ->sortBy('Product', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function getHeaders(): array
    {
        if ($this->summary) {
            return ['Product', 'Total In', 'Total Out', 'Net (In - Out)', 'No. of Movements'];
        }

        return ['Date', 'Product', 'Type', 'Quantity', 'Reason', 'Notes'];
    }

    public function getFilename(): string
    {
        $suffix = ($this->summary ? 'summary-' : '').($this->period ?? 'custom');

        if ($this->type) {
            $suffix .= '-'.$this->type;
        }

        return "inventory-report-{$suffix}-".now()->format('Y-m-d-His').'.csv';
    }
}
