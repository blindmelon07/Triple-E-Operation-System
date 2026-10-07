<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\CsvExportService;
use App\Support\CompanyLogo;
use App\Support\ReportBuilder\ArSummaryReportService;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

/**
 * Statement of Account summary report on accounts receivable — one line per
 * customer with an outstanding balance. See
 * App\Support\ReportBuilder\ArSummaryReportService for the figures.
 */
class ArSummaryReport extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static ?string $navigationLabel = 'SOA Summary (Receivables)';

    protected static ?string $title = 'Statement of Account Summary Report (Accounts Receivable)';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.ar-summary-report';

    #[Url]
    public ?int $customerId = null;

    /** '' (all), 'DUE' or 'OVER DUE' */
    #[Url]
    public string $status = '';

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: float}
     */
    public function report(): array
    {
        return (new ArSummaryReportService)->build($this->customerId, $this->status ?: null);
    }

    /**
     * @return array<int, string>
     */
    public function customerOptions(): array
    {
        return Customer::whereHas('sales', fn ($q) => $q->where('payment_status', '!=', 'paid')->where('is_voided', false))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function editSoaAction(): Action
    {
        return Action::make('editSoa')
            ->label('Edit')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->iconButton()
            ->color('gray')
            ->modalHeading(fn (array $arguments) => 'SOA details — '.Customer::find($arguments['customer'] ?? null)?->name)
            ->modalWidth('md')
            ->fillForm(function (array $arguments): array {
                $customer = Customer::findOrFail($arguments['customer']);

                $billingDate = $customer->soa_billing_date ?? now();

                return [
                    'soa_number' => $customer->soa_number ?: $customer->suggestSoaNumber($billingDate),
                    'soa_billing_date' => $billingDate->toDateString(),
                    'soa_notes' => $customer->soa_notes,
                ];
            })
            ->schema([
                TextInput::make('soa_number')
                    ->label('SOA No.')
                    ->maxLength(50)
                    ->helperText('Format: (100 + month)-YYYYMMDD + initials. A suggestion is filled in when blank.'),
                DatePicker::make('soa_billing_date')
                    ->label('Billing Date')
                    ->native(false),
                TextInput::make('soa_notes')
                    ->label('Notes')
                    ->placeholder('e.g. SOA received w/ new PO')
                    ->maxLength(255),
            ])
            ->action(function (array $data, array $arguments) {
                Customer::findOrFail($arguments['customer'])->update($data);

                Notification::make()->title('SOA details saved')->success()->send();
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->action(fn () => $this->exportPdf()),

            Action::make('exportCsv')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $report = $this->report();
        $money = fn (float $v) => number_format($v, 2);

        $rows = collect($report['rows'])
            ->map(fn (array $r) => [
                $r['id'],
                $r['name'],
                $r['soa_number'],
                $r['billing_date'] ? Carbon::parse($r['billing_date'])->format('j-M-y') : '',
                $money($r['receivable']),
                $money($r['running_total']),
                $r['status'],
                $r['notes'],
            ])
            ->push(['', '', 'TOTAL AMOUNT RECEIVABLE:', '', $money($report['total']), '', '', ''])
            ->push([])
            ->push(['PREPARED BY:', auth()->user()?->name]);

        $filename = 'soa-summary-receivables-'.now()->format('Y-m-d-His').'.csv';

        return (new CsvExportService)->export(
            ['NO.', 'CUSTOMER NAME', 'SOA No.', 'BILLING DATE', 'ACCOUNTS RECEIVABLE', 'TOTAL AMOUNT', 'STATUS', 'NOTES'],
            $rows,
            $filename,
        );
    }

    public function exportPdf(): StreamedResponse
    {
        $report = $this->report();

        $pdf = Pdf::loadView('exports.ar-summary-report-pdf', [
            'rows' => $report['rows'],
            'total' => $report['total'],
            'year' => now()->year,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
            'preparedBy' => auth()->user()?->name,
        ])->setPaper('a4', 'landscape');

        $filename = 'soa-summary-receivables-'.now()->format('Y-m-d-His').'.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
