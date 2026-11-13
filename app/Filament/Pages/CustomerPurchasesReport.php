<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\CsvExportService;
use App\Support\CompanyLogo;
use App\Support\ReportBuilder\CustomerPurchasesReportService;
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
 * Itemized purchase history for a single customer, grouped by date — see
 * App\Support\ReportBuilder\CustomerPurchasesReportService for the query.
 */
class CustomerPurchasesReport extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Customer Purchases';

    protected static ?string $title = 'Customer Purchases Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.customer-purchases-report';

    public ?int $customerId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public bool $generated = false;

    /** @var array<int, array<string, mixed>> */
    public array $groups = [];

    /** @var array{days: int, sales: int, items: int, quantity: float, amount: float} */
    public array $totals = ['days' => 0, 'sales' => 0, 'items' => 0, 'quantity' => 0, 'amount' => 0];

    /**
     * @return array<int, string>
     */
    public function customerOptions(): array
    {
        return Customer::orderBy('name')->pluck('name', 'id')->all();
    }

    public function updatedCustomerId(): void
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
        if (! $this->customerId) {
            Notification::make()->title('Please select a customer.')->warning()->send();

            return;
        }

        $result = (new CustomerPurchasesReportService)->build($this->customerId, $this->dateFrom, $this->dateTo);

        $this->groups = $result['groups'];
        $this->totals = $result['totals'];
        $this->generated = true;

        if (empty($this->groups)) {
            Notification::make()->title('No purchases found for this customer in that period.')->warning()->send();
        }
    }

    public function customerName(): ?string
    {
        return $this->customerId ? Customer::find($this->customerId)?->name : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedTableCells)
                ->color('success')
                ->disabled(fn () => ! $this->generated || empty($this->groups))
                ->action(fn () => $this->exportCsv()),

            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->disabled(fn () => ! $this->generated || empty($this->groups))
                ->action(fn () => $this->exportPdf()),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = ['Date', 'Reference', 'Item', 'Unit', 'Qty', 'Unit Price', 'Discount', 'Amount', 'Status'];

        $rows = collect();
        foreach ($this->groups as $group) {
            foreach ($group['items'] as $item) {
                $rows->push([
                    $group['date'],
                    $item['reference_number'],
                    $item['product'],
                    $item['unit'],
                    $item['quantity'],
                    number_format($item['unit_price'], 2),
                    number_format($item['discount'], 2),
                    number_format($item['amount'], 2),
                    $item['payment_status'],
                ]);
            }
            $rows->push([$group['date'], '', 'Subtotal', '', '', '', '', number_format($group['subtotal'], 2), '']);
        }
        $rows->push(['', '', 'GRAND TOTAL', '', '', '', '', number_format($this->totals['amount'], 2), '']);

        return (new CsvExportService)->export($headers, $rows, $this->filename('csv'));
    }

    public function exportPdf(): StreamedResponse
    {
        $pdf = Pdf::loadView('exports.customer-purchases-report-pdf', [
            'groups' => $this->groups,
            'totals' => $this->totals,
            'customerName' => $this->customerName(),
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'logoDataUri' => CompanyLogo::dataUri(),
            'preparedBy' => auth()->user()?->name,
        ])->setPaper('a4', 'portrait');

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $this->filename('pdf'), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function filename(string $extension): string
    {
        $slug = \Illuminate\Support\Str::slug($this->customerName() ?? 'customer');

        return "customer-purchases-{$slug}-".now()->format('Y-m-d-His').".{$extension}";
    }
}
