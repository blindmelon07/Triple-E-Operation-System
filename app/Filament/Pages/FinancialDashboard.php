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

class FinancialDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected string $view = 'filament.pages.financial-dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Financial Dashboard';

    protected static ?int $navigationSort = 9;

    public ?string $period = 'this_month';

    /** @var array<string, mixed> */
    public array $dashboardData = [];

    /** @var \Illuminate\Support\Collection<int, object> */
    public $monthlyTrend;

    /** @var \Illuminate\Support\Collection<int, object> */
    public $expensesByCategory;

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Financial Dashboard';
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

    public function loadDashboardData(): void
    {
        $accounting = (new AccountingService)->forPeriod($this->period);

        $this->dashboardData = $accounting->getDashboardSummary();
        $this->expensesByCategory = $accounting->getExpensesByCategory();
        $this->monthlyTrend = $accounting->getMonthlyProfitTrend(12);
    }

    public function updatedPeriod(): void
    {
        $this->loadDashboardData();
    }

    /**
     * Export the dashboard as PDF.
     */
    public function exportPdf(): \Illuminate\Http\Response
    {
        $exportService = new ReportExportService;

        return $exportService->exportDashboardPdf(
            $this->dashboardData,
            $this->expensesByCategory,
            $this->monthlyTrend,
            $this->period
        );
    }

    /**
     * Export the dashboard as Excel (CSV).
     */
    public function exportExcel(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $exportService = new ReportExportService;

        return $exportService->exportDashboardExcel(
            $this->dashboardData,
            $this->expensesByCategory,
            $this->monthlyTrend,
            $this->period
        );
    }

    /**
     * @return array<string, string>
     */
    public static function getPeriodOptions(): array
    {
        return [
            'today' => 'Today',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'this_year' => 'This Year',
        ];
    }
}
