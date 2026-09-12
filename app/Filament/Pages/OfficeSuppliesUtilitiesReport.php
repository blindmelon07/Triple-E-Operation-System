<?php

namespace App\Filament\Pages;

use App\Services\CsvExportService;
use App\Support\CompanyLogo;
use App\Support\ReportBuilder\OfficeSuppliesUtilitiesReportService;
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
 * Itemized ledger of office-supply and bills expenses (water, electricity,
 * internet, and office supplies) over a date range — see
 * App\Support\ReportBuilder\OfficeSuppliesUtilitiesReportService for the query.
 */
class OfficeSuppliesUtilitiesReport extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Office Supplies & Bills';

    protected static ?string $title = 'Office Supplies & Bills Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 23;

    protected string $view = 'filament.pages.office-supplies-utilities-report';

    public ?int $categoryId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $generated = false;

    /**
     * @var array<int, array{date: string, category: string, payee: string, reference_number: string, description: string, amount: float, running_total: float}>
     */
    public array $rows = [];

    /** @var array{amount: float} */
    public array $totals = [
        'amount' => 0,
    ];

    /** @var array<string, float> */
    public array $byCategory = [];

    /**
     * @return array<int, string>
     */
    public function categoryOptions(): array
    {
        return (new OfficeSuppliesUtilitiesReportService)->categoryOptions();
    }

    public function updatedCategoryId(): void
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
        $result = (new OfficeSuppliesUtilitiesReportService)->build($this->dateFrom, $this->dateTo, $this->categoryId);

        $this->rows = $result['rows']->all();
        $this->totals = $result['totals'];
        $this->byCategory = $result['byCategory'];
        $this->generated = true;

        if (empty($this->rows)) {
            Notification::make()->title('No office supply or bill expenses found for those filters.')->warning()->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->disabled(fn () => ! $this->generated || empty($this->rows))
                ->action(fn () => $this->exportCsv()),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->disabled(fn () => ! $this->generated || empty($this->rows))
                ->action(fn () => $this->exportPdf()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = ['Date', 'Category', 'Payee', 'Reference #', 'Description', 'Amount', 'Total'];

        $rows = collect($this->rows)->map(fn (array $r) => [
            $r['date'],
            $r['category'],
            $r['payee'],
            $r['reference_number'],
            $r['description'],
            number_format($r['amount'], 2),
            number_format($r['running_total'], 2),
        ]);

        $filename = 'office-supplies-utilities-report-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }

    public function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('exports.office-supplies-utilities-report-pdf', [
            'rows' => $this->rows,
            'totals' => $this->totals,
            'byCategory' => $this->byCategory,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'office-supplies-utilities-report-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
