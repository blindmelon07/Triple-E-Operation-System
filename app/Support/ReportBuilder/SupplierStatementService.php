<?php

namespace App\Support\ReportBuilder;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a supplier's statement of account: purchases and payments grouped by
 * calendar month, with a running balance carried across the supplier's full
 * history so the opening balance of the first displayed month is correct even
 * when the caller only asked for a narrower date window.
 */
class SupplierStatementService
{
    public function __construct(private int $supplierId) {}

    /**
     * @return array{
     *     supplier: Supplier,
     *     months: array<int, array{
     *         month: string,
     *         label: string,
     *         opening_balance: float,
     *         purchases: Collection<int, Purchase>,
     *         purchases_total: float,
     *         payments: Collection<int, PurchasePayment>,
     *         payments_total: float,
     *         closing_balance: float,
     *     }>,
     * }
     */
    public function build(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $supplier = Supplier::findOrFail($this->supplierId);

        $purchases = Purchase::query()
            ->where('supplier_id', $this->supplierId)
            ->orderBy('date')
            ->get();

        $payments = PurchasePayment::query()
            ->whereIn('purchase_id', $purchases->pluck('id'))
            ->orderBy('paid_date')
            ->get();

        $purchasesByMonth = $purchases->groupBy(fn (Purchase $p) => $p->date->format('Y-m'));
        $paymentsByMonth = $payments->groupBy(fn (PurchasePayment $p) => $p->paid_date->format('Y-m'));

        $allMonths = $purchasesByMonth->keys()
            ->merge($paymentsByMonth->keys())
            ->unique()
            ->sort()
            ->values();

        $from = $dateFrom ? Carbon::parse($dateFrom)->format('Y-m') : null;
        $to = $dateTo ? Carbon::parse($dateTo)->format('Y-m') : null;

        $months = [];
        $runningBalance = 0.0;

        foreach ($allMonths as $monthKey) {
            $monthPurchases = $purchasesByMonth->get($monthKey, collect());
            $monthPayments = $paymentsByMonth->get($monthKey, collect());

            $purchasesTotal = (float) $monthPurchases->sum('total');
            $paymentsTotal = (float) $monthPayments->sum('amount');

            $opening = $runningBalance;
            $closing = $opening + $purchasesTotal - $paymentsTotal;
            $runningBalance = $closing;

            if (($from && $monthKey < $from) || ($to && $monthKey > $to)) {
                continue;
            }

            $months[] = [
                'month' => $monthKey,
                'label' => Carbon::createFromFormat('Y-m', $monthKey)->format('F Y'),
                'opening_balance' => $opening,
                'purchases' => $monthPurchases->values(),
                'purchases_total' => $purchasesTotal,
                'payments' => $monthPayments->values(),
                'payments_total' => $paymentsTotal,
                'closing_balance' => $closing,
            ];
        }

        return [
            'supplier' => $supplier,
            'months' => $months,
        ];
    }
}
