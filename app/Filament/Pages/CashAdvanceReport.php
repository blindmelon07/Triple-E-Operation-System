<?php

namespace App\Filament\Pages;

use App\Models\Employee;
use App\Services\CsvExportService;
use App\Support\CompanyLogo;
use App\Support\ReportBuilder\CashAdvanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Cash advance monitoring sheet — a per-employee summary and a detailed
 * ledger with running balances. See
 * App\Support\ReportBuilder\CashAdvanceReportService for the figures.
 */
class CashAdvanceReport extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $navigationLabel = 'Cash Advance Monitoring';

    protected static ?string $title = 'Cash Advance Monitoring Sheet';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 24;

    protected string $view = 'filament.pages.cash-advance-report';

    /** 'summary' or 'ledger' */
    public string $mode = 'summary';

    public ?int $employeeId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $generated = false;

    /** @var array<int, array{employee: string, beginning: float, advances: float, deductions: float, ending: float}> */
    public array $summary = [];

    /** @var array{beginning: float, advances: float, deductions: float, ending: float} */
    public array $summaryTotals = [
        'beginning' => 0,
        'advances' => 0,
        'deductions' => 0,
        'ending' => 0,
    ];

    /** @var array<int, array{employee: string, beginning: float, rows: array<int, array<string, mixed>>, totals: array{advances: float, deductions: float, ending: float}}> */
    public array $ledgers = [];

    /**
     * @return array<int, string>
     */
    public function employeeOptions(): array
    {
        return Employee::whereHas('cashAdvances')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function updatedEmployeeId(): void
    {
        $this->generated = false;
    }

    public function updatedDateFrom(): void
    {
        $this->generated = false;
    }

    public function updatedDateTo(): void
    {
        $this->generated = false;
    }

    public function generate(): void
    {
        $result = (new CashAdvanceReportService)->build($this->dateFrom, $this->dateTo, $this->employeeId);

        $this->summary = $result['summary']->all();
        $this->summaryTotals = $result['summaryTotals'];
        $this->ledgers = $result['ledgers']->all();
        $this->generated = true;

        if (empty($this->summary)) {
            Notification::make()->title('No cash advance activity found for those filters.')->warning()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->disabled(fn () => ! $this->generated || empty($this->summary))
                ->action(fn () => $this->exportCsv()),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->disabled(fn () => ! $this->generated || empty($this->summary))
                ->action(fn () => $this->exportPdf()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $money = fn (float $v) => number_format($v, 2);

        if ($this->mode === 'ledger') {
            $headers = ['Employee', 'Date', 'Reference', 'Particulars', 'Cash Advance', 'Deduction', 'Balance'];
            $rows = collect();

            foreach ($this->ledgers as $l) {
                $rows->push([$l['employee'], '', '', 'Beginning balance', '', '', $money($l['beginning'])]);
                foreach ($l['rows'] as $r) {
                    $rows->push([$l['employee'], $r['date'], $r['reference'], $r['particulars'], $r['advance'] ? $money($r['advance']) : '', $r['deduction'] ? $money($r['deduction']) : '', $money($r['balance'])]);
                }
                $rows->push([$l['employee'], '', '', 'Total', $money($l['totals']['advances']), $money($l['totals']['deductions']), $money($l['totals']['ending'])]);
            }
        } else {
            $headers = ['Employee', 'Beginning Balance', 'Cash Advances', 'Deductions', 'Ending Balance'];
            $rows = collect($this->summary)
                ->map(fn (array $r) => [$r['employee'], $money($r['beginning']), $money($r['advances']), $money($r['deductions']), $money($r['ending'])])
                ->push(['TOTAL', $money($this->summaryTotals['beginning']), $money($this->summaryTotals['advances']), $money($this->summaryTotals['deductions']), $money($this->summaryTotals['ending'])]);
        }

        $filename = "cash-advance-{$this->mode}-".now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }

    public function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('exports.cash-advance-report-pdf', [
            'mode' => $this->mode,
            'summary' => $this->summary,
            'summaryTotals' => $this->summaryTotals,
            'ledgers' => $this->ledgers,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
            'preparedBy' => auth()->user()?->name,
        ])->setPaper('a4', 'portrait');

        $filename = "cash-advance-{$this->mode}-".now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
