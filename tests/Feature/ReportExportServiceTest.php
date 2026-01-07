<?php

use App\Models\User;
use App\Services\ReportExportService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can export profit loss report as PDF', function () {
    $reportData = [
        'revenue' => 50000,
        'cost_of_goods_sold' => 30000,
        'gross_profit' => 20000,
        'gross_profit_margin' => 40,
        'expenses' => 5000,
        'maintenance_costs' => 1000,
        'operating_profit' => 14000,
        'net_profit' => 14000,
        'net_profit_margin' => 28,
        'expenses_by_category' => collect([
            (object) ['category' => 'Utilities', 'total' => 2000],
            (object) ['category' => 'Supplies', 'total' => 3000],
        ]),
    ];

    $exportService = new ReportExportService;
    $response = $exportService->exportProfitLossPdf(
        $reportData,
        '2026-01-01',
        '2026-01-31'
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain('profit-loss-report');
});

it('can export profit loss report as Excel CSV', function () {
    $reportData = [
        'revenue' => 50000,
        'cost_of_goods_sold' => 30000,
        'gross_profit' => 20000,
        'gross_profit_margin' => 40,
        'expenses' => 5000,
        'maintenance_costs' => 1000,
        'operating_profit' => 14000,
        'net_profit' => 14000,
        'net_profit_margin' => 28,
        'expenses_by_category' => collect([
            (object) ['category' => 'Utilities', 'total' => 2000],
            (object) ['category' => 'Supplies', 'total' => 3000],
        ]),
    ];

    $exportService = new ReportExportService;
    $response = $exportService->exportProfitLossExcel(
        $reportData,
        '2026-01-01',
        '2026-01-31'
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain('profit-loss-report');
});

it('can export financial dashboard as PDF', function () {
    $dashboardData = [
        'revenue' => 50000,
        'collections' => 45000,
        'gross_profit' => 20000,
        'gross_profit_margin' => 40,
        'expenses' => 5000,
        'maintenance_costs' => 1000,
        'net_profit' => 14000,
        'net_profit_margin' => 28,
        'accounts_receivable' => 5000,
        'accounts_payable' => 3000,
    ];

    $expensesByCategory = collect([
        (object) ['category' => 'Utilities', 'total' => 2000],
        (object) ['category' => 'Supplies', 'total' => 3000],
    ]);

    $monthlyTrend = collect([
        (object) ['month' => '2025-11', 'revenue' => 45000, 'expenses' => 4000, 'profit' => 41000],
        (object) ['month' => '2025-12', 'revenue' => 48000, 'expenses' => 4500, 'profit' => 43500],
        (object) ['month' => '2026-01', 'revenue' => 50000, 'expenses' => 5000, 'profit' => 45000],
    ]);

    $exportService = new ReportExportService;
    $response = $exportService->exportDashboardPdf(
        $dashboardData,
        $expensesByCategory,
        $monthlyTrend,
        'this_month'
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain('financial-dashboard');
});

it('can export financial dashboard as Excel CSV', function () {
    $dashboardData = [
        'revenue' => 50000,
        'collections' => 45000,
        'gross_profit' => 20000,
        'gross_profit_margin' => 40,
        'expenses' => 5000,
        'maintenance_costs' => 1000,
        'net_profit' => 14000,
        'net_profit_margin' => 28,
        'accounts_receivable' => 5000,
        'accounts_payable' => 3000,
    ];

    $expensesByCategory = collect([
        (object) ['category' => 'Utilities', 'total' => 2000],
        (object) ['category' => 'Supplies', 'total' => 3000],
    ]);

    $monthlyTrend = collect([
        (object) ['month' => '2025-11', 'revenue' => 45000, 'expenses' => 4000, 'profit' => 41000],
        (object) ['month' => '2025-12', 'revenue' => 48000, 'expenses' => 4500, 'profit' => 43500],
        (object) ['month' => '2026-01', 'revenue' => 50000, 'expenses' => 5000, 'profit' => 45000],
    ]);

    $exportService = new ReportExportService;
    $response = $exportService->exportDashboardExcel(
        $dashboardData,
        $expensesByCategory,
        $monthlyTrend,
        'this_month'
    );

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toBe('text/csv');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain('financial-dashboard');
});

it('handles empty data in exports gracefully', function () {
    $reportData = [
        'revenue' => 0,
        'cost_of_goods_sold' => 0,
        'gross_profit' => 0,
        'gross_profit_margin' => 0,
        'expenses' => 0,
        'maintenance_costs' => 0,
        'operating_profit' => 0,
        'net_profit' => 0,
        'net_profit_margin' => 0,
        'expenses_by_category' => collect([]),
    ];

    $exportService = new ReportExportService;

    $pdfResponse = $exportService->exportProfitLossPdf($reportData, '2026-01-01', '2026-01-31');
    expect($pdfResponse->getStatusCode())->toBe(200);

    $excelResponse = $exportService->exportProfitLossExcel($reportData, '2026-01-01', '2026-01-31');
    expect($excelResponse->getStatusCode())->toBe(200);
});

it('handles negative profit (loss) correctly in exports', function () {
    $reportData = [
        'revenue' => 20000,
        'cost_of_goods_sold' => 15000,
        'gross_profit' => 5000,
        'gross_profit_margin' => 25,
        'expenses' => 10000,
        'maintenance_costs' => 2000,
        'operating_profit' => -7000,
        'net_profit' => -7000,
        'net_profit_margin' => -35,
        'expenses_by_category' => collect([
            (object) ['category' => 'Rent', 'total' => 8000],
            (object) ['category' => 'Utilities', 'total' => 2000],
        ]),
    ];

    $exportService = new ReportExportService;

    $pdfResponse = $exportService->exportProfitLossPdf($reportData, '2026-01-01', '2026-01-31');
    expect($pdfResponse->getStatusCode())->toBe(200);

    $excelResponse = $exportService->exportProfitLossExcel($reportData, '2026-01-01', '2026-01-31');
    expect($excelResponse->getStatusCode())->toBe(200);
});
