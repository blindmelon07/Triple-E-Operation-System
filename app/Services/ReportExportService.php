<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    /**
     * Export Profit & Loss report as PDF.
     *
     * @param  array<string, mixed>  $reportData
     */
    public function exportProfitLossPdf(array $reportData, string $startDate, string $endDate): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('exports.profit-loss-pdf', [
            'reportData' => $reportData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now()->format('F d, Y h:i A'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'profit-loss-report-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
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
     * Export Aging Report as PDF.
     */
    public function exportAgingPdf(): \Illuminate\Http\Response
    {
        $receivables = Sale::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
            ->with('customer')
            ->orderBy('due_date')
            ->get();

        $payables = Purchase::where('payment_status', '!=', 'paid')
            ->whereNotNull('due_date')
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
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('aging-report-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * Export Aging Report as Excel (CSV).
     */
    public function exportAgingExcel(): StreamedResponse
    {
        $filename = 'aging-report-'.now()->format('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Title
            fputcsv($file, ['Accounts Receivable & Payable Aging Report']);
            fputcsv($file, ['Generated:', now()->format('F d, Y h:i A')]);
            fputcsv($file, []);

            // ── RECEIVABLES ──────────────────────────────────────────────
            $receivables = Sale::where('payment_status', '!=', 'paid')
                ->whereNotNull('due_date')
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
    ): \Illuminate\Http\Response {
        $pdf = Pdf::loadView('exports.financial-dashboard-pdf', [
            'dashboardData' => $dashboardData,
            'expensesByCategory' => $expensesByCategory,
            'monthlyTrend' => $monthlyTrend,
            'period' => $period,
            'generatedAt' => now()->format('F d, Y h:i A'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        $filename = 'financial-dashboard-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
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
}
