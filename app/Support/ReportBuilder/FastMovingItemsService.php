<?php

namespace App\Support\ReportBuilder;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Ranks products by how much of them actually sold in a date window. Only
 * non-voided items on non-voided sales count, so returns and exchanges
 * (which void the original sale_items row) don't inflate a product's total.
 * Quantities are converted to each product's base unit before ranking, so a
 * product sold by the box and by the piece is compared like-for-like.
 */
class FastMovingItemsService
{
    /**
     * @return array<int, array{
     *     rank: int,
     *     product_id: int,
     *     name: string,
     *     category: ?string,
     *     unit: ?string,
     *     quantity_sold: float,
     *     transactions: int,
     *     sales_amount: float,
     *     stock_in: float,
     *     stock_out: float,
     *     current_stock: float,
     *     avg_daily: ?float,
     *     days_of_stock: ?float,
     * }>
     */
    public function build(?string $dateFrom, ?string $dateTo, ?int $categoryId = null, int $limit = 20): array
    {
        $byUnit = $this->baseQuery($dateFrom, $dateTo, $categoryId)
            ->groupBy('sale_items.product_id', 'sale_items.unit')
            ->selectRaw('sale_items.product_id, sale_items.unit, SUM(sale_items.quantity) as qty')
            ->toBase()
            ->get();

        if ($byUnit->isEmpty()) {
            return [];
        }

        $byProduct = $this->baseQuery($dateFrom, $dateTo, $categoryId)
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id, SUM(sale_items.price) as amount, COUNT(DISTINCT sale_items.sale_id) as transactions')
            ->toBase()
            ->get()
            ->keyBy('product_id');

        $products = Product::query()
            ->with(['category', 'inventory', 'unitPrices'])
            ->whereIn('id', $byUnit->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        // Every stock movement in the window (purchases, adjustments, returns
        // in; sales, exchanges out) — same base-unit ledger the Inventory
        // In/Out report reads, so the two reports agree.
        $movements = InventoryMovement::query()
            ->whereIn('product_id', $products->keys())
            ->when($dateFrom, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
            ->when($dateTo, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date))
            ->groupBy('product_id')
            ->selectRaw("product_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END) as total_in, SUM(CASE WHEN type = 'out' THEN quantity ELSE 0 END) as total_out")
            ->toBase()
            ->get()
            ->keyBy('product_id');

        $days = ($dateFrom && $dateTo)
            ? Carbon::parse($dateFrom)->startOfDay()->diffInDays(Carbon::parse($dateTo)->startOfDay()) + 1
            : null;

        $baseQuantities = [];

        foreach ($byUnit as $line) {
            $product = $products->get($line->product_id);

            if (! $product) {
                continue;
            }

            $factor = $line->unit ? $product->conversionFactorFor($line->unit) : 1.0;
            $baseQuantities[$line->product_id] = ($baseQuantities[$line->product_id] ?? 0) + (float) $line->qty * $factor;
        }

        $rows = collect($baseQuantities)->map(function (float $quantity, int $productId) use ($products, $byProduct, $movements, $days) {
            $product = $products->get($productId);
            $stock = (float) ($product->inventory?->quantity ?? 0);
            $avgDaily = $days ? $quantity / $days : null;

            return [
                'product_id' => $productId,
                'name' => $product->name,
                'category' => $product->category?->name,
                'unit' => $product->unit?->value,
                'quantity_sold' => round($quantity, 2),
                'transactions' => (int) ($byProduct->get($productId)?->transactions ?? 0),
                'sales_amount' => round((float) ($byProduct->get($productId)?->amount ?? 0), 2),
                'stock_in' => round((float) ($movements->get($productId)?->total_in ?? 0), 2),
                'stock_out' => round((float) ($movements->get($productId)?->total_out ?? 0), 2),
                'current_stock' => $stock,
                'avg_daily' => $avgDaily !== null ? round($avgDaily, 2) : null,
                'days_of_stock' => $avgDaily ? round(max($stock, 0) / $avgDaily, 1) : null,
            ];
        })
            ->sortBy([['quantity_sold', 'desc'], ['sales_amount', 'desc']])
            ->take($limit)
            ->values();

        return $rows->map(fn (array $row, int $i) => ['rank' => $i + 1, ...$row])->all();
    }

    private function baseQuery(?string $dateFrom, ?string $dateTo, ?int $categoryId): Builder
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNotNull('sale_items.product_id')
            ->where('sale_items.is_voided', false)
            ->where('sales.is_voided', false)
            ->when($dateFrom, fn (Builder $q, string $date) => $q->whereDate('sales.date', '>=', $date))
            ->when($dateTo, fn (Builder $q, string $date) => $q->whereDate('sales.date', '<=', $date))
            ->when($categoryId, fn (Builder $q, int $id) => $q->whereHas('product', fn (Builder $p) => $p->where('category_id', $id)));
    }
}
