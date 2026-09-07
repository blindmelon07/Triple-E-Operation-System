<?php

namespace App\Filament\Pages;

use App\Models\Vehicle;
use App\Services\CsvExportService;
use App\Support\ReportBuilder\GasMaintenanceReportService;
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
 * Per-vehicle summary of gas (fuel) and maintenance spend over a date range —
 * see App\Support\ReportBuilder\GasMaintenanceReportService for the query.
 */
class GasMaintenanceReport extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?string $navigationLabel = 'Gas & Maintenance Report';

    protected static ?string $title = 'Gas & Maintenance Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.gas-maintenance-report';

    public ?int $vehicleId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $generated = false;

    /**
     * Plain arrays, not Vehicle models — the fuel/maintenance totals are
     * computed sums, not real columns, so they wouldn't survive Livewire
     * re-hydrating each Vehicle from the DB by key on the next request
     * (e.g. when the Export actions fire).
     *
     * @var array<int, array{id: int, plate_number: string, full_name: string, fuel_total: float, fuel_liters: float, maintenance_total: float, grand_total: float}>
     */
    public array $rows = [];

    /** @var array{fuel_total: float, fuel_liters: float, maintenance_total: float, grand_total: float} */
    public array $totals = [
        'fuel_total' => 0,
        'fuel_liters' => 0,
        'maintenance_total' => 0,
        'grand_total' => 0,
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
        $result = (new GasMaintenanceReportService)->build($this->dateFrom, $this->dateTo, $this->vehicleId);

        $this->rows = $result['rows']->map(fn (Vehicle $v) => [
            'id' => $v->id,
            'plate_number' => $v->plate_number,
            'full_name' => $v->full_name,
            'fuel_total' => $v->fuel_total,
            'fuel_liters' => $v->fuel_liters,
            'maintenance_total' => $v->maintenance_total,
            'grand_total' => $v->grand_total,
        ])->all();
        $this->totals = $result['totals'];
        $this->generated = true;

        if (empty($this->rows)) {
            Notification::make()->title('No gas or maintenance activity found for those filters.')->warning()->send();
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
        $headers = ['Vehicle', 'Plate #', 'Fuel Cost', 'Fuel Liters', 'Maintenance Cost', 'Total'];

        $rows = collect($this->rows)->map(fn (array $v) => [
            $v['full_name'],
            $v['plate_number'],
            number_format($v['fuel_total'], 2),
            number_format($v['fuel_liters'], 2),
            number_format($v['maintenance_total'], 2),
            number_format($v['grand_total'], 2),
        ]);

        $filename = 'gas-maintenance-report-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export($headers, $rows, $filename);
    }

    public function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('exports.gas-maintenance-report-pdf', [
            'rows' => $this->rows,
            'totals' => $this->totals,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'generatedAt' => now()->format('F d, Y h:i A'),
        ])->setPaper('a4', 'portrait');

        $filename = 'gas-maintenance-report-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
