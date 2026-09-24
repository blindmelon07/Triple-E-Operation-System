<?php

namespace App\Support\ReportBuilder;

use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a supplier's statement of account: purchases and payments grouped by
 * calendar month. Only entries dated inside the requested window are listed;
 * everything before it is rolled into the first month's opening balance.
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

        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        // Anything dated before the window only feeds the opening balance.
        $runningBalance = (float) $purchases->filter(fn (Purchase $p) => $from && $p->date->lt($from))->sum('total')
            - (float) $payments->filter(fn (PurchasePayment $p) => $from && $p->paid_date->lt($from))->sum('amount');

        $inRange = fn (CarbonInterface $date) => (! $from || $date->gte($from)) && (! $to || $date->lte($to));

        $purchasesByMonth = $purchases
            ->filter(fn (Purchase $p) => $inRange($p->date))
            ->groupBy(fn (Purchase $p) => $p->date->format('Y-m'));
        $paymentsByMonth = $payments
            ->filter(fn (PurchasePayment $p) => $inRange($p->paid_date))
            ->groupBy(fn (PurchasePayment $p) => $p->paid_date->format('Y-m'));

        $allMonths = $purchasesByMonth->keys()
            ->merge($paymentsByMonth->keys())
            ->unique()
            ->sort()
            ->values();

        $months = [];

        foreach ($allMonths as $monthKey) {
            $monthPurchases = $purchasesByMonth->get($monthKey, collect());
            $monthPayments = $paymentsByMonth->get($monthKey, collect());

            $purchasesTotal = (float) $monthPurchases->sum('total');
            $paymentsTotal = (float) $monthPayments->sum('amount');

            $opening = $runningBalance;
            $closing = $opening + $purchasesTotal - $paymentsTotal;
            $runningBalance = $closing;

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
