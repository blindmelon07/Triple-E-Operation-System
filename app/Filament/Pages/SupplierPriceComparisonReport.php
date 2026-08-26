<?php

namespace App\Filament\Pages;

use App\Exports\SupplierPriceComparisonExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\CsvExportService;
use App\Services\ReportExportService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class SupplierPriceComparisonReport extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;

    protected string $view = 'filament.pages.supplier-price-comparison-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Supplier Price Comparison';

    protected static ?string $title = 'Supplier Price Comparison';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    /**
     * Every supplier that has quoted at least one base price. Drives the
     * dynamic per-supplier columns below — the matrix grows and shrinks
     * automatically as prices are recorded, instead of showing all suppliers.
     */
    protected function getPricedSuppliers(): Collection
    {
        return Supplier::query()
            ->whereHas('productPrices')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function table(Table $table): Table
    {
        $suppliers = $this->getPricedSuppliers();

        $columns = [
            TextColumn::make('name')
                ->label('Product')
                ->searchable()
                ->sortable(),
            TextColumn::make('category.name')
                ->label('Category')
                ->sortable(),
        ];

        foreach ($suppliers as $supplier) {
            $columns[] = TextColumn::make("supplier_price_{$supplier->id}")
                ->label($supplier->name)
                ->alignEnd()
                ->getStateUsing(function (Product $record) use ($supplier) {
                    $price = $record->supplierPrices->firstWhere('supplier_id', $supplier->id);

                    return $price ? '₱'.number_format((float) $price->base_price, 2) : '—';
                });
        }

        return $table
            ->query(
                Product::query()
                    ->whereHas('supplierPrices')
                    ->with(['category', 'supplierPrices'])
            )
            ->columns($columns)
            ->filters([
                Filter::make('category_id')
                    ->form([
                        Select::make('category_id')
                            ->label('Category')
                            ->options(Category::pluck('name', 'id'))
                            ->placeholder('All Categories'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['category_id'],
                            fn (Builder $query, $categoryId): Builder => $query->where('category_id', $categoryId),
                        );
                    }),
            ])
            ->striped()
            ->paginated([10, 25, 50, 'all'])
            ->emptyStateHeading('No supplier base prices recorded yet')
            ->emptyStateDescription('Add a supplier and a base price to a product\'s "Supplier Base Prices" section to see it compared here.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_pdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->form([
                    Select::make('category_id')
                        ->label('Category (Optional)')
                        ->options(Category::pluck('name', 'id'))
                        ->placeholder('All Categories'),
                ])
                ->action(fn (array $data) => (new ReportExportService)->exportSupplierPriceComparisonPdf(
                    $data['category_id'] ?? null
                )),

            Action::make('export_csv')
                ->label('Export to CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->form([
                    Select::make('category_id')
                        ->label('Category (Optional)')
                        ->options(Category::pluck('name', 'id'))
                        ->placeholder('All Categories'),
                ])
                ->action(function (array $data) {
                    $export = new SupplierPriceComparisonExport($data['category_id'] ?? null);

                    return (new CsvExportService)->export(
                        $export->getHeaders(),
                        $export->getData(),
                        $export->getFilename(),
                    );
                }),
        ];
    }
}
