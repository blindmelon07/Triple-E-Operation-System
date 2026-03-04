# TOS Project Memory

## Stack
- Laravel + Filament v3 (admin panel)
- Alpine.js for POS frontend
- barryvdh/laravel-dompdf for PDF generation
- Custom CsvExportService for CSV exports

## Key Paths
- POS controller: `app/Http/Controllers/POSController.php`
- POS views: `resources/views/pos/`
- Filament resources: `app/Filament/Resources/`
- Exports: `app/Exports/`
- Services: `app/Services/CsvExportService.php`

## Patterns

### CSV Export
- Create an export class in `app/Exports/` with `query()`, `getData()`, `getHeaders()`, `getFilename()`
- Use `CsvExportService::export(headers, data, filename)` to stream download
- See `SalesReportExport`, `SalesSummaryExport`, `InventoryReportExport` as examples

### PDF Generation (dompdf)
- Use `Pdf::loadView('view.name', compact(...))->setPaper('a4', 'portrait')->download($filename)`
- Import: `use Barryvdh\DomPDF\Facade\Pdf;`
- Blade views for PDF use `DejaVu Sans` font for best compatibility

### Filament Header Actions
- Header actions go in `getHeaderActions()` in the Page/ListRecords class
- Use `Action::make()->form([...])->action(fn)` for modal forms with download

## Company Info
- Name: Tri-E Enterprises
- Address: Maharlika Highway, Cabidan, Sorsogon City
- Phone: (+639) 993-052-2540

## Features Built
- Sales summary CSV download on Sales list page (ListSales.php)
- Register closure PDF report auto-opens on close (all session sales + customer names)
