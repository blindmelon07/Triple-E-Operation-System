<?php

namespace App\Support\ReportBuilder;

use App\Enums\ProductUnit;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;

/**
 * Builds an itemized purchase history for one customer over an optional date
 * range: every line item the customer bought, grouped by sale date (oldest
 * first), with a subtotal per date and a grand total. Voided sales and
 * individually voided line items are excluded so totals match receipts.
 */
class CustomerPurchasesReportService
{
    /**
     * @return array{
     *     groups: array<int, array{
     *         date: string,
     *         items: array<int, array{reference_number: string, product: string, unit: string, quantity: float, unit_price: float, discount: float, amount: float, payment_status: string}>,
     *         sales_count: int,
     *         subtotal: float,
     *     }>,
     *     totals: array{days: int, sales: int, items: int, quantity: float, amount: float},
     * }
     */
    public function build(int $customerId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sales = Sale::query()
            ->with(['sale_items' => fn ($q) => $q->active()->with('product')->orderBy('id')])
            ->where('customer_id', $customerId)
            ->where('is_voided', false)
            ->when($dateFrom, fn (Builder $q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $q) => $q->whereDate('date', '<=', $dateTo))
            ->orderBy('date')
            ->orderBy('id')
            ->get()
            ->filter(fn (Sale $sale) => $sale->sale_items->isNotEmpty());

        $groups = [];
        $totals = ['days' => 0, 'sales' => 0, 'items' => 0, 'quantity' => 0.0, 'amount' => 0.0];

        foreach ($sales->groupBy(fn (Sale $sale) => $sale->date->format('Y-m-d')) as $daySales) {
            $items = [];
            $subtotal = 0.0;

            foreach ($daySales as $sale) {
                foreach ($sale->sale_items as $item) {
                    $row = $this->itemRow($sale, $item);
                    $items[] = $row;
                    $subtotal += $row['amount'];
                    $totals['quantity'] += $row['quantity'];
                }
            }

            $groups[] = [
                'date' => $daySales->first()->date->format('M d, Y'),
                'items' => $items,
                'sales_count' => $daySales->count(),
                'subtotal' => round($subtotal, 2),
            ];

            $totals['days']++;
            $totals['sales'] += $daySales->count();
            $totals['items'] += count($items);
            $totals['amount'] += $subtotal;
        }

        $totals['amount'] = round($totals['amount'], 2);

        return ['groups' => $groups, 'totals' => $totals];
    }

    /**
     * @return array{reference_number: string, product: string, unit: string, quantity: float, unit_price: float, discount: float, amount: float, payment_status: string}
     */
    private function itemRow(Sale $sale, SaleItem $item): array
    {
        $quantity = (float) $item->quantity;
        $unitPrice = (float) $item->unit_price;
        $amount = (float) $item->price;

        return [
            'reference_number' => $sale->reference_number ?: '#'.$sale->id,
            'product' => $item->is_manual
                ? (string) $item->product_description
                : ($item->product?->name ?? (string) $item->product_description),
            'unit' => ProductUnit::tryFrom((string) $item->unit)?->label() ?? (string) $item->unit,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => round(max(0, $unitPrice * $quantity - $amount), 2),
            'amount' => $amount,
            'payment_status' => ucfirst((string) $sale->payment_status),
        ];
    }
}
