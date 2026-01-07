<?php

namespace App\Filament\Pages;

use App\Services\AccountingService;
use App\Services\ReportExportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class ProfitLossReport extends Page
{
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
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => $this->exportPdf()),

            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
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
    public function exportPdf(): \Illuminate\Http\Response
    {
        $exportService = new ReportExportService;

        return $exportService->exportProfitLossPdf(
            $this->reportData,
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
            $this->reportData,
            $this->startDate,
            $this->endDate
        );
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
