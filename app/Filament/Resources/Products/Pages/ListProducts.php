<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Download CSV Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(fn () => $this->downloadTemplate()),
            Action::make('import')
                ->label('Import Products')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Placeholder::make('instructions')
                        ->content('CSV columns: Name (required), Price, Category, Stock, Unit')
                        ->columnSpanFull(),
                    FileUpload::make('file')
                        ->label('CSV File')
                        ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', '.csv'])
                        ->disk('local')
                        ->directory('imports')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $file = is_array($data['file']) ? $data['file'][0] : $data['file'];
                    $this->importProducts($file);
                }),
            CreateAction::make(),
        ];
    }

    protected function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Price', 'Category', 'Stock', 'Unit']);
            fputcsv($file, ['Sample Product', '100.00', 'Electronics', '50', 'piece']);
            fclose($file);
        }, 'products_import_template.csv');
    }

    protected function importProducts(string $filePath): void
    {
        $service = new ProductImportService;
        $result = $service->importFromCsv($filePath);

        if ($result['success']) {
            Notification::make()
                ->title('Import Successful')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Import Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }
}
