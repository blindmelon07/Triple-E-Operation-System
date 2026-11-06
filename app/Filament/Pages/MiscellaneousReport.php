<?php

namespace App\Filament\Pages;

use App\Models\Vehicle;
use App\Services\CsvExportService;
use App\Support\CompanyLogo;
use App\Support\ReportBuilder\MiscellaneousReportService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Itemized miscellaneous-expense ledger over a date range — see
 * App\Support\ReportBuilder\MiscellaneousReportService for the query.
 */
class MiscellaneousReport extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Miscellaneous Report';

    protected static ?string $title = 'Miscellaneous Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 23;

    protected string $view = 'filament.pages.miscellaneous-report';

    public ?int $vehicleId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $generated = false;

    /**
     * @var array<int, array{date: string, reference_number: string, description: string, payee: string, si_dr_number: string, truck: string, plate_number: string, amount: float, running_total: float}>
     */
    public array $rows = [];

    /** @var array{count: int, amount: float} */
    public array $totals = [
        'count' => 0,
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
        $result = (new MiscellaneousReportService)->build($this->dateFrom, $this->dateTo, $this->vehicleId ?: null);

        $this->rows = $result['rows']->all();
        $this->totals = $result['totals'];
        $this->generated = true;

        if (empty($this->rows)) {
            Notification::make()->title('No miscellaneous expenses found for those filters.')->warning()->send();
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
        $headers = ['Date', 'Reference', 'Description', 'Paid To', 'SI/DR / OR #', 'Truck/Unit', 'Plate #', 'Amount', 'Total'];

        $rows = collect($this->rows)->map(fn (array $r) => [
            $r['date'],
            $r['reference_number'],
            $r['description'],
            $r['payee'],
            $r['si_dr_number'],
            $r['truck'],
            $r['plate_number'],
            number_format($r['amount'], 2),
            number_format($r['running_total'], 2),
        ]);

        $filename = 'miscellaneous-report-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }

    public function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('exports.miscellaneous-report-pdf', [
            'rows' => $this->rows,
            'totals' => $this->totals,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'vehicleName' => $this->vehicleId ? Vehicle::find($this->vehicleId)?->display_name : null,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
            'preparedBy' => auth()->user()?->name,
        ])->setPaper('a4', 'landscape');

        $filename = 'miscellaneous-report-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
