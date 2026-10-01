<?php

namespace App\Filament\Pages;

use App\Models\MaintenanceRecord;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Services\CsvExportService;
use App\Support\CompanyLogo;
use App\Support\ReportBuilder\MaintenanceReportService;
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
 * Maintenance-expense ledger over a date range, either itemized or grouped
 * per supplier with subtotals — see
 * App\Support\ReportBuilder\MaintenanceReportService for the query.
 */
class MaintenanceReport extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Maintenance Report';

    protected static ?string $title = 'Maintenance Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 22;

    protected string $view = 'filament.pages.maintenance-report';

    /** 'itemized' = one ledger in entry order; 'per_supplier' = records grouped under each supplier with subtotals. */
    public string $viewMode = 'itemized';

    public ?int $vehicleId = null;

    public ?int $supplierId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $generated = false;

    /**
     * @var array<int, array{date: string, supplier: string, si_number: string, po_number: string, amount: float, running_total: float}>
     */
    public array $rows = [];

    /**
     * @var array<int, array{supplier: string, count: int, subtotal: float, rows: array<int, array<string, mixed>>}>
     */
    public array $groups = [];

    /** @var array{amount: float} */
    public array $totals = [
        'amount' => 0,
    ];

    /**
     * @return array<int, string>
     */
    public function vehicleOptions(): array
    {
        return Vehicle::orderBy('plate_number')
            ->get()
            ->mapWithKeys(fn (Vehicle $v) => [$v->id => $v->display_name])
            ->all();
    }

    /**
     * Only suppliers that actually appear on a maintenance record.
     *
     * @return array<int, string>
     */
    public function supplierOptions(): array
    {
        return Supplier::query()
            ->whereIn('id', MaintenanceRecord::query()->whereNotNull('supplier_id')->select('supplier_id'))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function isPerSupplier(): bool
    {
        return $this->viewMode === 'per_supplier';
    }

    public function updatedViewMode(): void
    {
        $this->generated = false;
    }

    public function updatedSupplierId(): void
    {
        $this->generated = false;
    }

    public function updatedVehicleId(): void
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
        $service = new MaintenanceReportService;

        if ($this->isPerSupplier()) {
            $result = $service->buildPerSupplier($this->dateFrom, $this->dateTo, $this->vehicleId, $this->supplierId);
            $this->groups = $result['groups'];
            $this->rows = [];
        } else {
            $result = $service->build($this->dateFrom, $this->dateTo, $this->vehicleId, $this->supplierId);
            $this->rows = $result['rows']->all();
            $this->groups = [];
        }

        $this->totals = $result['totals'];
        $this->generated = true;

        if (! $this->hasResults()) {
            Notification::make()->title('No maintenance activity found for those filters.')->warning()->send();
        }
    }

    public function hasResults(): bool
    {
        return $this->isPerSupplier() ? ! empty($this->groups) : ! empty($this->rows);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->disabled(fn () => ! $this->generated || ! $this->hasResults())
                ->action(fn () => $this->exportCsv()),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->disabled(fn () => ! $this->generated || ! $this->hasResults())
                ->action(fn () => $this->exportPdf()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        if ($this->isPerSupplier()) {
            return $this->exportPerSupplierCsv();
        }

        $headers = ['Date', 'Supplier', 'SI /DR #', 'PO#', 'Amount', 'Total'];

        $rows = collect($this->rows)->map(fn (array $r) => [
            $r['date'],
            $r['supplier'],
            $r['si_number'],
            $r['po_number'],
            number_format($r['amount'], 2),
            number_format($r['running_total'], 2),
        ]);

        $filename = 'maintenance-report-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }

    public function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('exports.maintenance-report-pdf', [
            'perSupplier' => $this->isPerSupplier(),
            'groups' => $this->groups,
            'rows' => $this->rows,
            'totals' => $this->totals,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ])->setPaper('a4', 'portrait');

        $filename = ($this->isPerSupplier() ? 'maintenance-report-per-supplier-' : 'maintenance-report-').now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function exportPerSupplierCsv(): StreamedResponse
    {
        $headers = ['Supplier', 'Date', 'Vehicle', 'SI /DR #', 'PO#', 'Amount', 'Total'];

        $rows = collect();

        foreach ($this->groups as $group) {
            foreach ($group['rows'] as $r) {
                $rows->push([
                    $group['supplier'],
                    $r['date'],
                    $r['vehicle'],
                    $r['si_number'],
                    $r['po_number'],
                    number_format($r['amount'], 2),
                    number_format($r['running_total'], 2),
                ]);
            }

            $rows->push([$group['supplier'].' Subtotal', '', '', '', '', number_format($group['subtotal'], 2), '']);
        }

        $rows->push(['Grand Total', '', '', '', '', number_format($this->totals['amount'], 2), '']);

        $filename = 'maintenance-report-per-supplier-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }
}
