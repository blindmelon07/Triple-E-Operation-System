<?php

namespace App\Filament\Pages;

use App\Services\AccountingService;
use App\Services\ReportExportService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class ProfitLossReport extends Page
{
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected string $view = 'filament.pages.profit-loss-report';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Profit & Loss';

    protected static ?int $navigationSort = 10;

    public ?string $period = 'this_month';

    public ?string $startDate = null;

    public ?string $endDate = null;

    /** @var array<string, mixed> */
    public array $reportData = [];

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->endOfMonth()->format('Y-m-d');
        $this->generateReport();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Profit & Loss Report';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(fn () => $this->exportPdf()),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->action(fn () => $this->exportExcel()),
        ];
    }

    public function generateReport(): void
    {
        $accounting = new AccountingService;

        if ($this->period !== 'custom') {
            $accounting->forPeriod($this->period);

            // Update date fields to reflect the selected period
            $this->startDate = $this->getStartDateForPeriod()->format('Y-m-d');
            $this->endDate = $this->getEndDateForPeriod()->format('Y-m-d');
        } else {
            $accounting->setDateRange(
                $this->startDate ? \Carbon\Carbon::parse($this->startDate) : null,
                $this->endDate ? \Carbon\Carbon::parse($this->endDate) : null
            );
        }

        $this->reportData = $accounting->getProfitAndLossStatement();
        $this->reportData['expenses_by_category'] = $accounting->getExpensesByCategory();
    }

    protected function getStartDateForPeriod(): \Carbon\Carbon
    {
        return match ($this->period) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'this_week' => now()->startOfWeek(),
            'last_week' => now()->subWeek()->startOfWeek(),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            'this_quarter' => now()->startOfQuarter(),
            'last_quarter' => now()->subQuarter()->startOfQuarter(),
            'this_year' => now()->startOfYear(),
            'last_year' => now()->subYear()->startOfYear(),
            default => now()->startOfMonth(),
        };
    }

    protected function getEndDateForPeriod(): \Carbon\Carbon
    {
        return match ($this->period) {
            'today' => now()->endOfDay(),
            'yesterday' => now()->subDay()->endOfDay(),
            'this_week' => now()->endOfWeek(),
            'last_week' => now()->subWeek()->endOfWeek(),
            'this_month' => now()->endOfMonth(),
            'last_month' => now()->subMonth()->endOfMonth(),
            'this_quarter' => now()->endOfQuarter(),
            'last_quarter' => now()->subQuarter()->endOfQuarter(),
            'this_year' => now()->endOfYear(),
            'last_year' => now()->subYear()->endOfYear(),
            default => now()->endOfMonth(),
        };
    }

    public function updatedPeriod(): void
    {
        $this->generateReport();
    }

    public function updatedStartDate(): void
    {
        if ($this->period === 'custom') {
            $this->generateReport();
        }
    }

    public function updatedEndDate(): void
    {
        if ($this->period === 'custom') {
            $this->generateReport();
        }
    }

    /**
     * Export the report as PDF.
     */
    public function exportPdf(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $exportService = new ReportExportService;

        return $exportService->exportProfitLossPdf(
            $this->flattenedReportDataForExport(),
            $this->startDate,
            $this->endDate
        );
    }

    /**
     * Export the report as Excel (CSV).
     */
    public function exportExcel(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $exportService = new ReportExportService;

        return $exportService->exportProfitLossExcel(
            $this->flattenedReportDataForExport(),
            $this->startDate,
            $this->endDate
        );
    }

    /**
     * AccountingService::getProfitAndLossStatement() nests revenue/COGS/
     * expenses under sub-keys (e.g. revenue.total, operating_expenses.
     * general_expenses) — that's what the on-screen page reads via
     * $this->reportData. The PDF/CSV export views were written against a
     * flat {revenue, cost_of_goods_sold, expenses, maintenance_costs, ...}
     * shape instead; passing the nested array straight through makes
     * number_format() throw (array given) on export. Adapt here, without
     * touching $this->reportData, which the page view still needs nested.
     *
     * @return array<string, mixed>
     */
    protected function flattenedReportDataForExport(): array
    {
        return [
            ...$this->reportData,
            'revenue' => $this->reportData['revenue']['total'] ?? 0,
            'cost_of_goods_sold' => $this->reportData['cost_of_goods_sold']['total'] ?? 0,
            'expenses' => $this->reportData['operating_expenses']['general_expenses'] ?? 0,
            'maintenance_costs' => $this->reportData['operating_expenses']['maintenance'] ?? 0,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function getPeriodOptions(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
            'custom' => 'Custom Range',
        ];
    }
}
