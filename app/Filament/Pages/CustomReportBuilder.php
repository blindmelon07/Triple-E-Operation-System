<?php

namespace App\Filament\Pages;

use App\Models\Supplier;
use App\Services\CsvExportService;
use App\Support\CompanyLogo;
use App\Support\ReportBuilder\ReportModules;
use App\Support\ReportBuilder\ReportQueryService;
use App\Support\ReportBuilder\SupplierStatementService;
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
 * Lets an admin/manager build their own ad-hoc report: pick a data module,
 * pick which of its columns to include, optionally filter by date range and
 * a couple of module-specific fields, then preview it on screen or export it
 * as CSV/PDF. See App\Support\ReportBuilder\ReportModules for what modules
 * and columns are available — it's the single whitelist this whole page
 * (and its exports) reads from.
 */
class CustomReportBuilder extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Report Builder';

    protected static ?string $title = 'Custom Report Builder';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.custom-report-builder';

    /** Row cap shared by the on-screen preview and the CSV export — cheap to hold in memory and to stream as text. */
    private const ROW_LIMIT = 2000;

    /** Selectable page sizes for the on-screen preview (CSV/PDF exports are unaffected by this — it's just the on-screen view). */
    private const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    /**
     * DomPDF has to lay out the whole table in memory to compute column widths,
     * and that cost grows fast with row count — empirically, a wide table north
     * of ~500-800 rows blows the default 512MB PHP memory limit and the request
     * dies with a fatal error instead of a PDF. 250 leaves real margin under
     * that (measured ~180MB for a similarly-wide 300-row table) while still
     * being a genuinely useful export size; CSV has no such ceiling, so that's
     * what a bigger report should use instead.
     */
    private const PDF_ROW_LIMIT = 250;

    /** 'data' = the generic column-picker builder below; 'supplier_statement' = the per-supplier, per-month statement of account. */
    public string $reportMode = 'data';

    public ?int $supplierId = null;

    public ?string $statementDateFrom = null;

    public ?string $statementDateTo = null;

    public bool $statementGenerated = false;

    /** @var array<int, array<string, mixed>> */
    public array $statementMonths = [];

    public ?string $module = null;

    /** @var array<int, string> */
    public array $selectedColumns = [];

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    /** @var array<string, mixed> */
    public array $filterValues = [];

    public bool $generated = false;

    public int $resultCount = 0;

    /** True total matching the current filters, ignoring ROW_LIMIT — for "showing 2,000 of 2,543" messaging. */
    public int $totalMatchCount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public int $page = 1;

    public int $perPage = 50;

    /**
     * @return array<string, string>
     */
    public function moduleOptions(): array
    {
        return ReportModules::moduleOptions();
    }

    /**
     * @return array<int, string>
     */
    public function supplierOptions(): array
    {
        return Supplier::orderBy('name')->pluck('name', 'id')->all();
    }

    public function updatedReportMode(): void
    {
        $this->statementGenerated = false;
        $this->generated = false;
    }

    public function updatedSupplierId(): void
    {
        $this->statementGenerated = false;
    }

    public function updatedStatementDateFrom(): void
    {
        $this->statementGenerated = false;
    }

    public function updatedStatementDateTo(): void
    {
        $this->statementGenerated = false;
    }

    public function generateStatement(): void
    {
        if (! $this->supplierId) {
            Notification::make()->title('Pick a supplier first.')->warning()->send();

            return;
        }

        $statement = (new SupplierStatementService($this->supplierId))
            ->build($this->statementDateFrom, $this->statementDateTo);

        $this->statementMonths = $statement['months'];
        $this->statementGenerated = true;
    }

    /**
     * @return array<string, string>
     */
    public function columnOptions(): array
    {
        if (! $this->module) {
            return [];
        }

        return ReportModules::get($this->module)['columns'] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function filterDefinitions(): array
    {
        if (! $this->module) {
            return [];
        }

        return ReportModules::get($this->module)['filters'] ?? [];
    }

    public function moduleHasDateRange(): bool
    {
        if (! $this->module) {
            return false;
        }

        return ! empty(ReportModules::get($this->module)['date_column'] ?? null);
    }

    public function updatedModule(): void
    {
        $this->selectedColumns = array_keys($this->columnOptions());
        $this->filterValues = [];
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->generated = false;
        $this->rows = [];
        $this->page = 1;
    }

    public function selectAllColumns(): void
    {
        $this->selectedColumns = array_keys($this->columnOptions());
    }

    public function clearColumns(): void
    {
        $this->selectedColumns = [];
    }

    // Any change to what's being asked for invalidates the last preview/export,
    // so the Export buttons (gated on $generated) can't ship stale rows that no
    // longer match what's on screen.
    public function updatedSelectedColumns(): void
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

    public function updatedFilterValues(): void
    {
        $this->generated = false;
    }

    public function generate(): void
    {
        if (! $this->module) {
            Notification::make()->title('Pick a data module first.')->warning()->send();

            return;
        }

        if (empty($this->selectedColumns)) {
            Notification::make()->title('Pick at least one column first.')->warning()->send();

            return;
        }

        $service = new ReportQueryService($this->module);
        $filters = $this->currentFilters();
        $rows = $service->rows($this->orderedSelectedColumns(), $filters, self::ROW_LIMIT);

        $this->rows = $rows->toArray();
        $this->resultCount = $rows->count();
        $this->totalMatchCount = $service->totalCount($filters);
        $this->page = 1;
        $this->generated = true;
    }

    /**
     * @return array<int, string>
     */
    public function orderedSelectedColumns(): array
    {
        // Render columns in the module's defined order, not whatever order the
        // checkboxes happened to be ticked in.
        return array_values(array_intersect(array_keys($this->columnOptions()), $this->selectedColumns));
    }

    /**
     * @return array<string, mixed>
     */
    private function currentFilters(): array
    {
        return [
            ...$this->filterValues,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function perPageOptions(): array
    {
        return array_combine(self::PER_PAGE_OPTIONS, self::PER_PAGE_OPTIONS);
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
    }

    public function lastPage(): int
    {
        return max(1, (int) ceil(count($this->rows) / max(1, $this->perPage)));
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, min($page, $this->lastPage()));
    }

    public function previousPage(): void
    {
        $this->goToPage($this->page - 1);
    }

    public function nextPage(): void
    {
        $this->goToPage($this->page + 1);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function paginatedRows(): array
    {
        $page = min($this->page, $this->lastPage());

        return array_slice($this->rows, ($page - 1) * $this->perPage, $this->perPage);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->visible(fn () => $this->reportMode === 'data')
                ->disabled(fn () => ! $this->generated)
                ->action(fn () => $this->exportCsv()),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn () => $this->reportMode === 'data')
                ->disabled(fn () => ! $this->generated)
                ->action(fn () => $this->exportPdf()),

            Action::make('exportStatementCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->visible(fn () => $this->reportMode === 'supplier_statement')
                ->disabled(fn () => ! $this->statementGenerated)
                ->action(fn () => $this->exportStatementCsv()),

            Action::make('exportStatementPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn () => $this->reportMode === 'supplier_statement')
                ->disabled(fn () => ! $this->statementGenerated)
                ->action(fn () => $this->exportStatementPdf()),
        ];
    }

    public function exportStatementCsv(): StreamedResponse
    {
        $headers = ['Month', 'Date', 'SI #', 'P.O #', 'Total', 'Total Amount'];

        $rows = collect();

        foreach ($this->statementMonths as $month) {
            $runningTotal = 0.0;

            foreach ($month['purchases'] as $purchase) {
                $runningTotal += (float) $purchase->total;
                $rows->push([$month['label'], $purchase->date->format('Y-m-d'), $purchase->si_number ?: '', $purchase->po_number ?: '', number_format($purchase->total, 2), number_format($runningTotal, 2)]);
            }
        }

        $supplierName = Supplier::find($this->supplierId)?->name ?? 'supplier';
        $filename = 'statement-of-account-'.\Illuminate\Support\Str::slug($supplierName).'-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }

    public function exportStatementPdf(): StreamedResponse
    {
        $supplier = Supplier::findOrFail($this->supplierId);

        $pdf = Pdf::loadView('exports.supplier-statement-pdf', [
            'supplier' => $supplier,
            'months' => $this->statementMonths,
            'dateFrom' => $this->statementDateFrom,
            'dateTo' => $this->statementDateTo,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = 'statement-of-account-'.\Illuminate\Support\Str::slug($supplier->name).'-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $columns = $this->orderedSelectedColumns();
        $labels = $this->columnOptions();
        $headers = array_map(fn (string $key) => $labels[$key], $columns);

        $rows = collect($this->rows)->map(fn (array $row) => array_values($row));

        $filename = 'custom-report-'.$this->module.'-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }

    public function exportPdf(): StreamedResponse
    {
        $columns = $this->orderedSelectedColumns();
        $labels = $this->columnOptions();
        $headers = array_map(fn (string $key) => $labels[$key], $columns);

        // $this->rows already holds up to ROW_LIMIT (2000) — DomPDF can't safely
        // lay out a table anywhere near that many rows (see PDF_ROW_LIMIT), so
        // the PDF gets a much smaller slice of it. CSV export doesn't have this
        // ceiling — it's plain streamed text, not a rendered layout.
        $pdfRows = array_slice($this->rows, 0, self::PDF_ROW_LIMIT);

        $pdf = Pdf::loadView('exports.custom-report-pdf', [
            'title' => ReportModules::get($this->module)['label'] ?? 'Custom Report',
            'headers' => $headers,
            'rows' => $pdfRows,
            'totalMatchCount' => $this->totalMatchCount,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'logoDataUri' => CompanyLogo::dataUri(),
        ])->setPaper('a4', 'landscape');

        $filename = 'custom-report-'.$this->module.'-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
