<?php

namespace App\Filament\Pages;

use App\Models\Vehicle;
use App\Services\CsvExportService;
use App\Support\CompanyLogo;
use App\Support\ReportBuilder\GasReportService;
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
 * Itemized gas-expense ledger over a date range — see
 * App\Support\ReportBuilder\GasReportService for the query.
 */
class GasReport extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?string $navigationLabel = 'Gas Report';

    protected static ?string $title = 'Gas Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.gas-report';

    public ?int $vehicleId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $generated = false;

    /**
     * @var array<int, array{date: string, truck: string, plate_number: string, si_dr_number: string, fuel_station: string, liters: float, unit_price: float, amount: float, running_total: float}>
     */
    public array $rows = [];

    /** @var array{liters: float, amount: float} */
    public array $totals = [
        'liters' => 0,
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
        $result = (new GasReportService)->build($this->dateFrom, $this->dateTo, $this->vehicleId);

        $this->rows = $result['rows']->all();
        $this->totals = $result['totals'];
        $this->generated = true;

        if (empty($this->rows)) {
            Notification::make()->title('No gas activity found for those filters.')->warning()->send();
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
        $headers = ['Date', 'Truck/Unit', 'SI/DR #', 'Gas Station', 'Ltrs', 'Unit', 'Amount', 'Total'];

        $rows = collect($this->rows)->map(fn (array $r) => [
            $r['date'],
            $r['truck'],
            $r['si_dr_number'],
            $r['fuel_station'],
            number_format($r['liters'], 2),
            number_format($r['unit_price'], 2),
            number_format($r['amount'], 2),
            number_format($r['running_total'], 2),
        ]);

        $filename = 'gas-report-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }

    public function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('exports.gas-report-pdf', [
            'rows' => $this->rows,
            'totals' => $this->totals,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
        ])->setPaper('a4', 'landscape');

        $filename = 'gas-report-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
