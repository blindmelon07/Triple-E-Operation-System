<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payroll;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Supplier;
use App\Support\CompanyLogo;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfInstance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Export Profit & Loss report as PDF.
     *
     * @param  array<string, mixed>  $reportData
     */
    public function exportProfitLossPdf(array $reportData, string $startDate, string $endDate): StreamedResponse
    {
        $pdf = Pdf::loadView('exports.profit-loss-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'profit-loss-report-'.now()->format('Y-m-d').'.pdf';

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Profit & Loss report as Excel (CSV).
     *
     * @param  array<string, mixed>  $reportData
     */
    public function exportProfitLossExcel(array $reportData, string $startDate, string $endDate): StreamedResponse
    {
        $filename = 'profit-loss-report-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($reportData, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            // Write header
            fputcsv($file, ['Profit & Loss Report']);
            fputcsv($file, ['Period:', $startDate.' to '.$endDate]);
            fputcsv($file, ['Generated:', now()->format('F d, Y h:i A')]);
            fputcsv($file, []);

            // Revenue Section
            fputcsv($file, ['REVENUE']);
            fputcsv($file, ['Description', 'Amount']);
            fputcsv($file, ['Total Sales Revenue', number_format($reportData['revenue'] ?? 0, 2)]);
            fputcsv($file, []);

            // Cost of Goods Sold Section
            fputcsv($file, ['COST OF GOODS SOLD']);
            fputcsv($file, ['Description', 'Amount']);
            fputcsv($file, ['Cost of Goods Sold', number_format($reportData['cost_of_goods_sold'] ?? 0, 2)]);
            fputcsv($file, []);

            // Gross Profit
            fputcsv($file, ['GROSS PROFIT', number_format($reportData['gross_profit'] ?? 0, 2)]);
            fputcsv($file, ['Gross Profit Margin', number_format($reportData['gross_profit_margin'] ?? 0, 1).'%']);
            fputcsv($file, []);

            // Operating Expenses Section
            fputcsv($file, ['OPERATING EXPENSES']);
            fputcsv($file, ['Description', 'Amount']);

            if (isset($reportData['expenses_by_category'])) {
                foreach ($reportData['expenses_by_category'] as $expense) {
                    fputcsv($file, [$expense->category, number_format($expense->total, 2)]);
                }
            }

            fputcsv($file, ['Maintenance Costs', number_format($reportData['maintenance_costs'] ?? 0, 2)]);
            fputcsv($file, ['Total Operating Expenses', number_format(($reportData['expenses'] ?? 0) + ($reportData['maintenance_costs'] ?? 0), 2)]);
            fputcsv($file, []);

            // Operating Profit
            fputcsv($file, ['OPERATING PROFIT', number_format($reportData['operating_profit'] ?? 0, 2)]);
            fputcsv($file, []);

            // Net Profit
            fputcsv($file, ['NET PROFIT', number_format($reportData['net_profit'] ?? 0, 2)]);
            fputcsv($file, ['Net Profit Margin', number_format($reportData['net_profit_margin'] ?? 0, 1).'%']);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export the Sales Report (itemized) as PDF, using the same period/date-range
     * filtering as SalesReportExport so the CSV and PDF always agree.
     */
    public function exportSalesReportPdf(?string $period = null, ?string $dateFrom = null, ?string $dateUntil = null): StreamedResponse
    {
        $export = new \App\Exports\SalesReportExport(period: $period, dateFrom: $dateFrom, dateUntil: $dateUntil);

        $sales = $export->query()->orderBy('date')->get();

        $pdf = Pdf::loadView('exports.sales-report-pdf', [
            'sales' => $sales,
            'periodLabel' => $this->salesReportPeriodLabel($period, $dateFrom, $dateUntil),
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'sales-report-'.($period ?? 'custom').'-'.now()->format('Y-m-d-His').'.pdf';

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Human-readable label for the Sales Report PDF header, mirroring the
     * period options offered in the export modal.
     */
    private function salesReportPeriodLabel(?string $period, ?string $dateFrom, ?string $dateUntil): string
    {
        if ($dateFrom && $dateUntil) {
            $from = Carbon::parse($dateFrom);
            $to = Carbon::parse($dateUntil);

            return $from->isSameDay($to)
                ? $from->format('F d, Y')
                : $from->format('F d, Y').' – '.$to->format('F d, Y');
        }

        return match ($period) {
            'today' => 'Today ('.now()->format('F d, Y').')',
            'yesterday' => 'Yesterday ('.now()->subDay()->format('F d, Y').')',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month ('.now()->format('F Y').')',
            'last_month' => 'Last Month ('.now()->subMonth()->format('F Y').')',
            'this_year' => 'This Year ('.now()->format('Y').')',
            default => 'All Time',
        };
    }

    /**
     * Export Aging Report as PDF.
     */
    public function exportAgingPdf(?int $customerId = null, ?int $supplierId = null): StreamedResponse
    {
        $receivables = Sale::where('payment_status', '!=', 'paid')
            ->where('is_voided', false)
            ->whereNotNull('due_date')
            ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
            ->with('customer')
            ->orderBy('due_date')
            ->get();

        $payables = Purchase::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->with('supplier')
            ->orderBy('due_date')
            ->get();

        $stats = [
            'receivables' => [
                'current'       => $receivables->filter(fn ($s) => $s->days_overdue === null)->sum('balance'),
                '1_30'          => $receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue <= 30)->sum('balance'),
                '31_60'         => $receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 30 && $s->days_overdue <= 60)->sum('balance'),
                '61_90'         => $receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 60 && $s->days_overdue <= 90)->sum('balance'),
                'over_90'       => $receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 90)->sum('balance'),
                'total'         => $receivables->sum('balance'),
                'overdue_count' => $receivables->filter(fn ($s) => $s->days_overdue !== null)->count(),
            ],
            'payables' => [
                'current'       => $payables->filter(fn ($p) => $p->days_overdue === null)->sum('balance'),
                '1_30'          => $payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue <= 30)->sum('balance'),
                '31_60'         => $payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 30 && $p->days_overdue <= 60)->sum('balance'),
                '61_90'         => $payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 60 && $p->days_overdue <= 90)->sum('balance'),
                'over_90'       => $payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 90)->sum('balance'),
                'total'         => $payables->sum('balance'),
                'overdue_count' => $payables->filter(fn ($p) => $p->days_overdue !== null)->count(),
            ],
        ];

        $pdf = Pdf::loadView('exports.aging-report-pdf', [
            'receivables' => $receivables,
            'payables'    => $payables,
            'stats'       => $stats,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'customerFilterName' => $customerId ? Customer::find($customerId)?->name : null,
            'supplierFilterName' => $supplierId ? Supplier::find($supplierId)?->name : null,
            'logoDataUri' => CompanyLogo::dataUri(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $this->streamPdf($pdf, 'aging-report-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Export Aging Report as Excel (CSV).
     */
    public function exportAgingExcel(?int $customerId = null, ?int $supplierId = null): StreamedResponse
    {
        $filename = 'aging-report-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($customerId, $supplierId) {
            $file = fopen('php://output', 'w');

            // Title
            fputcsv($file, ['Accounts Receivable & Payable Aging Report']);
            fputcsv($file, ['Generated:', now()->format('F d, Y h:i A')]);
            if ($customerId && ($customerName = Customer::find($customerId)?->name)) {
                fputcsv($file, ['Filtered by Customer:', $customerName]);
            }
            if ($supplierId && ($supplierName = Supplier::find($supplierId)?->name)) {
                fputcsv($file, ['Filtered by Supplier:', $supplierName]);
            }
            fputcsv($file, []);

            // ── RECEIVABLES ──────────────────────────────────────────────
            $receivables = Sale::where('payment_status', '!=', 'paid')
                ->where('is_voided', false)
                ->whereNotNull('due_date')
                ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
                ->with('customer')
                ->orderBy('due_date')
                ->get();

            fputcsv($file, ['ACCOUNTS RECEIVABLE (Customers Owe You)']);
            fputcsv($file, ['Invoice #', 'Customer', 'Invoice Date', 'Due Date', 'Total', 'Paid', 'Balance', 'Days Overdue', 'Aging Bucket']);

            foreach ($receivables as $sale) {
                $daysOverdue = $sale->days_overdue;
                fputcsv($file, [
                    'INV-'.$sale->id,
                    $sale->customer?->name ?? 'N/A',
                    $sale->date,
                    $sale->due_date,
                    number_format($sale->total, 2),
                    number_format($sale->amount_paid, 2),
                    number_format($sale->balance, 2),
                    $daysOverdue !== null ? $daysOverdue.' days' : 'Current',
                    $sale->aging_bucket,
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['RECEIVABLES SUMMARY']);
            fputcsv($file, ['Bucket', 'Amount']);
            fputcsv($file, ['Current', number_format($receivables->filter(fn ($s) => $s->days_overdue === null)->sum('balance'), 2)]);
            fputcsv($file, ['1-30 Days', number_format($receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue <= 30)->sum('balance'), 2)]);
            fputcsv($file, ['31-60 Days', number_format($receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 30 && $s->days_overdue <= 60)->sum('balance'), 2)]);
            fputcsv($file, ['61-90 Days', number_format($receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 60 && $s->days_overdue <= 90)->sum('balance'), 2)]);
            fputcsv($file, ['Over 90 Days', number_format($receivables->filter(fn ($s) => $s->days_overdue !== null && $s->days_overdue > 90)->sum('balance'), 2)]);
            fputcsv($file, ['Total Outstanding', number_format($receivables->sum('balance'), 2)]);
            fputcsv($file, []);

            // ── PAYABLES ─────────────────────────────────────────────────
            $payables = Purchase::where('payment_status', '!=', 'paid')
                ->whereNotNull('due_date')
                ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
                ->with('supplier')
                ->orderBy('due_date')
                ->get();

            fputcsv($file, ['ACCOUNTS PAYABLE (You Owe Suppliers)']);
            fputcsv($file, ['PO #', 'Supplier', 'Purchase Date', 'Due Date', 'Total', 'Paid', 'Balance', 'Days Overdue', 'Aging Bucket']);

            foreach ($payables as $purchase) {
                $daysOverdue = $purchase->days_overdue;
                fputcsv($file, [
                    'PO-'.$purchase->id,
                    $purchase->supplier?->name ?? 'N/A',
                    $purchase->date,
                    $purchase->due_date,
                    number_format($purchase->total, 2),
                    number_format($purchase->amount_paid, 2),
                    number_format($purchase->balance, 2),
                    $daysOverdue !== null ? $daysOverdue.' days' : 'Current',
                    $purchase->aging_bucket,
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['PAYABLES SUMMARY']);
            fputcsv($file, ['Bucket', 'Amount']);
            fputcsv($file, ['Current', number_format($payables->filter(fn ($p) => $p->days_overdue === null)->sum('balance'), 2)]);
            fputcsv($file, ['1-30 Days', number_format($payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue <= 30)->sum('balance'), 2)]);
            fputcsv($file, ['31-60 Days', number_format($payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 30 && $p->days_overdue <= 60)->sum('balance'), 2)]);
            fputcsv($file, ['61-90 Days', number_format($payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 60 && $p->days_overdue <= 90)->sum('balance'), 2)]);
            fputcsv($file, ['Over 90 Days', number_format($payables->filter(fn ($p) => $p->days_overdue !== null && $p->days_overdue > 90)->sum('balance'), 2)]);
            fputcsv($file, ['Total Outstanding', number_format($payables->sum('balance'), 2)]);

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export the Supplier Price Comparison as PDF — a matrix of every product
     * (that has at least one recorded supplier base price) against every
     * supplier who has quoted a price for anything, optionally scoped to a
     * single category.
     */
    public function exportSupplierPriceComparisonPdf(?int $categoryId = null): StreamedResponse
    {
        $export = new \App\Exports\SupplierPriceComparisonExport($categoryId);

        $pdf = Pdf::loadView('exports.supplier-price-comparison-pdf', [
            'rows' => $export->getData(),
            'suppliers' => $export->getSuppliers(),
            'categoryName' => $categoryId ? \App\Models\Category::find($categoryId)?->name : null,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $this->streamPdf($pdf, 'supplier-price-comparison-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Export a single customer's Statement of Account as PDF — a chronological
     * ledger of invoices (debits) and payments (credits) with a running balance,
     * optionally scoped to a date range with a carried-forward opening balance.
     */
    public function exportCustomerStatementPdf(Customer $customer, ?string $dateFrom = null, ?string $dateTo = null): StreamedResponse
    {
        $sales = Sale::where('customer_id', $customer->id)
            ->where('is_voided', false)
            ->orderBy('date')
            ->orderBy('created_at')
            ->get();

        $paymentsBySale = SalePayment::whereIn('sale_id', $sales->pluck('id'))
            ->orderBy('created_at')
            ->get()
            ->groupBy('sale_id');

        // Build one chronological ledger: each invoice is a debit on its sale date,
        // each payment (whether logged as a down payment/settlement, or collected
        // in full at creation with no separate ledger row) is a credit on the date
        // it was actually collected.
        $entries = collect();

        foreach ($sales as $sale) {
            $invoiceNumber = 'INV-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT);

            $entries->push([
                'date'   => $sale->date,
                'label'  => "Invoice {$invoiceNumber}",
                'detail' => $sale->payment_term_days ? "Net {$sale->payment_term_days} terms" : 'Due on receipt',
                'debit'  => (float) $sale->total,
                'credit' => 0.0,
            ]);

            $loggedForSale = $paymentsBySale->get($sale->id, collect());

            // Money collected at sale creation only gets a sale_payments row when it
            // was a partial down payment on a credit sale; a fully-paid sale (credit
            // or not) has no such row, so recover it from what's left of amount_paid.
            $unlogged = round((float) $sale->amount_paid - (float) $loggedForSale->sum('amount'), 2);
            if ($unlogged > 0.01) {
                $entries->push([
                    'date'   => $sale->date,
                    'label'  => "Payment — {$invoiceNumber}",
                    'detail' => ucfirst($sale->payment_method ?? 'cash'),
                    'debit'  => 0.0,
                    'credit' => $unlogged,
                ]);
            }

            foreach ($loggedForSale as $payment) {
                $entries->push([
                    'date'   => $payment->created_at,
                    'label'  => "Payment — {$invoiceNumber}",
                    'detail' => ucfirst($payment->payment_method).($payment->reference_number ? " Ref# {$payment->reference_number}" : ''),
                    'debit'  => 0.0,
                    'credit' => (float) $payment->amount,
                ]);
            }
        }

        $entries = $entries->sortBy('date')->values();

        $from = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $to = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;

        $openingBalance = $from
            ? (float) $entries->filter(fn ($e) => $e['date'] < $from)->sum(fn ($e) => $e['debit'] - $e['credit'])
            : 0.0;

        $periodEntries = $entries->filter(function ($e) use ($from, $to) {
            return (! $from || $e['date'] >= $from) && (! $to || $e['date'] <= $to);
        })->values();

        $running = $openingBalance;
        $periodEntries = $periodEntries->map(function ($e) use (&$running) {
            $running += $e['debit'] - $e['credit'];
            $e['running_balance'] = $running;

            return $e;
        });

        $pdf = Pdf::loadView('exports.customer-statement-pdf', [
            'customer'       => $customer,
            'entries'        => $periodEntries,
            'openingBalance' => $openingBalance,
            'closingBalance' => $running,
            'totalInvoiced'  => (float) $periodEntries->sum('debit'),
            'totalPaid'      => (float) $periodEntries->sum('credit'),
            'dateFrom'       => $from,
            'dateTo'         => $to,
            'generatedAt'    => now()->format('F d, Y h:i A'),
            'logoDataUri'    => CompanyLogo::dataUri(),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'statement-of-account-'.Str::slug($customer->name).'-'.now()->format('Y-m-d').'.pdf';

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Financial Dashboard as PDF.
     *
     * @param  array<string, mixed>  $dashboardData
     * @param  \Illuminate\Support\Collection<int, object>  $expensesByCategory
     * @param  \Illuminate\Support\Collection<int, object>  $monthlyTrend
     */
    public function exportDashboardPdf(
        array $dashboardData,
        $expensesByCategory,
        $monthlyTrend,
        string $period
    ): StreamedResponse {
        $pdf = Pdf::loadView('exports.financial-dashboard-pdf', [
            'dashboardData' => $dashboardData,
            'expensesByCategory' => $expensesByCategory,
            'monthlyTrend' => $monthlyTrend,
            'period' => $period,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'financial-dashboard-'.now()->format('Y-m-d').'.pdf';

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export a Payroll (with its items) as a printable PDF.
     */
    public function exportPayrollPdf(Payroll $payroll): StreamedResponse
    {
        $payroll->load(['payrollItems.employee', 'generatedBy', 'approvedBy']);

        $pdf = Pdf::loadView('exports.payroll-pdf', [
            'payroll' => $payroll,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'payroll-'.$payroll->payroll_number.'.pdf';

        return $this->streamPdf($pdf, $filename);
    }

    /**
     * Export Financial Dashboard as Excel (CSV).
     *
     * @param  array<string, mixed>  $dashboardData
     * @param  \Illuminate\Support\Collection<int, object>  $expensesByCategory
     * @param  \Illuminate\Support\Collection<int, object>  $monthlyTrend
     */
    public function exportDashboardExcel(
        array $dashboardData,
        $expensesByCategory,
        $monthlyTrend,
        string $period
    ): StreamedResponse {
        $filename = 'financial-dashboard-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($dashboardData, $expensesByCategory, $monthlyTrend, $period) {
            $file = fopen('php://output', 'w');

            // Write header
            fputcsv($file, ['Financial Dashboard Report']);
            fputcsv($file, ['Period:', ucwords(str_replace('_', ' ', $period))]);
            fputcsv($file, ['Generated:', now()->format('F d, Y h:i A')]);
            fputcsv($file, []);

            // Key Metrics Section
            fputcsv($file, ['KEY METRICS']);
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Revenue', number_format($dashboardData['revenue'] ?? 0, 2)]);
            fputcsv($file, ['Collections', number_format($dashboardData['collections'] ?? 0, 2)]);
            fputcsv($file, ['Gross Profit', number_format($dashboardData['gross_profit'] ?? 0, 2)]);
            fputcsv($file, ['Gross Profit Margin', number_format($dashboardData['gross_profit_margin'] ?? 0, 1).'%']);
            fputcsv($file, ['Operating Expenses', number_format(($dashboardData['expenses'] ?? 0) + ($dashboardData['maintenance_costs'] ?? 0), 2)]);
            fputcsv($file, ['Maintenance Costs', number_format($dashboardData['maintenance_costs'] ?? 0, 2)]);
            fputcsv($file, ['Net Profit', number_format($dashboardData['net_profit'] ?? 0, 2)]);
            fputcsv($file, ['Net Profit Margin', number_format($dashboardData['net_profit_margin'] ?? 0, 1).'%']);
            fputcsv($file, []);

            // Accounts Section
            fputcsv($file, ['ACCOUNTS']);
            fputcsv($file, ['Accounts Receivable', number_format($dashboardData['accounts_receivable'] ?? 0, 2)]);
            fputcsv($file, ['Accounts Payable', number_format($dashboardData['accounts_payable'] ?? 0, 2)]);
            fputcsv($file, []);

            // Expense Breakdown Section
            if ($expensesByCategory && count($expensesByCategory) > 0) {
                fputcsv($file, ['EXPENSE BREAKDOWN']);
                fputcsv($file, ['Category', 'Amount']);
                foreach ($expensesByCategory as $expense) {
                    fputcsv($file, [$expense->category, number_format($expense->total, 2)]);
                }
                fputcsv($file, []);
            }

            // Monthly Trend Section
            if ($monthlyTrend && count($monthlyTrend) > 0) {
                fputcsv($file, ['MONTHLY TREND (12 Months)']);
                fputcsv($file, ['Month', 'Revenue', 'Expenses', 'Profit/Loss']);
                foreach ($monthlyTrend as $month) {
                    fputcsv($file, [
                        \Carbon\Carbon::parse($month->month.'-01')->format('M Y'),
                        number_format($month->revenue, 2),
                        number_format($month->expenses, 2),
                        number_format($month->profit, 2),
                    ]);
                }
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Stream a rendered DomPDF instance as a StreamedResponse instead of
     * using DomPDF's own download() (a plain Illuminate\Http\Response).
     *
     * Several of these exports are returned directly from a Filament/
     * Livewire action closure. Livewire only treats a StreamedResponse or
     * BinaryFileResponse as "this is a file" and base64-encodes it into a
     * download effect; a plain Response instead falls through to Livewire's
     * normal JSON 'returns' payload, where the raw PDF bytes (never valid
     * UTF-8 — PDF is a binary format) make json_encode() throw "Malformed
     * UTF-8 characters", turning every such PDF export into a 500. Routing
     * everything through here avoids that regardless of call site.
     */
    private function streamPdf(PdfInstance $pdf, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

}
