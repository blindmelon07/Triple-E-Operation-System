<?php

namespace App\Filament\Pages;

use App\Exports\ProductsReportExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\CsvExportService;
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
use UnitEnum;

class ProductsReport extends Page implements HasTable
{
    use InteractsWithTable;
    use HasPageShield;
    protected string $view = 'filament.pages.products-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Products Report';

    protected static ?string $title = 'Products Report';

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export to CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Select::make('category_id')
                        ->label('Category (Optional)')
                        ->options(Category::pluck('name', 'id'))
                        ->placeholder('All Categories'),
                    Select::make('supplier_id')
                        ->label('Supplier (Optional)')
                        ->options(Supplier::pluck('name', 'id'))
                        ->placeholder('All Suppliers'),
                ])
                ->action(function (array $data) {
                    $export = new ProductsReportExport(
                        categoryId: $data['category_id'] ?? null,
                        supplierId: $data['supplier_id'] ?? null,
                    );

                    return (new CsvExportService)->export(
                        $export->getHeaders(),
                        $export->getData(),
                        $export->getFilename(),
                    );
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->with(['category', 'supplier', 'inventory']))
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->formatStateUsing(fn ($state) => $state?->label()),
                TextColumn::make('price')
                    ->label('Price')
                    ->money('PHP')
                    ->sortable(),
                TextColumn::make('inventory.quantity')
                    ->label('Current Stock')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sales_total')
                    ->label('Total Sold')
                    ->numeric()
                    ->getStateUsing(function (Product $record) {
                        return $record->saleItems()->sum('quantity');
                    }),
            ])
            ->filters([
                Filter::make('category_id')
                    ->form([
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label('Category'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['category_id'],
                            fn (Builder $query, $categoryId): Builder => $query->where('category_id', $categoryId),
                        );
                    }),
                Filter::make('supplier_id')
                    ->form([
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->label('Supplier'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['supplier_id'],
                            fn (Builder $query, $supplierId): Builder => $query->where('supplier_id', $supplierId),
                        );
                    }),
            ])
            ->paginated([10, 25, 50])
            ->striped();
    }
}
