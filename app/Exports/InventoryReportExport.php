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
    ) {}

    public function query(): Builder
    {
        $query = InventoryMovement::query()->with(['product']);

        return $this->applyDateFilter($query, 'created_at');
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

    public function getHeaders(): array
    {
        return ['Date', 'Product', 'Type', 'Quantity', 'Reason', 'Notes'];
    }

    public function getFilename(): string
    {
        $suffix = $this->period ?? 'custom';

        return "inventory-report-{$suffix}-".now()->format('Y-m-d-His').'.csv';
    }
}
