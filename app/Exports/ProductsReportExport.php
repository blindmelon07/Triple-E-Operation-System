<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProductsReportExport
{
    public function __construct(
        protected ?int $categoryId = null,
        protected ?int $supplierId = null,
    ) {}

    public function query(): Builder
    {
        $query = Product::query()->with(['category', 'supplier', 'inventory']);

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        if ($this->supplierId) {
            $query->where('supplier_id', $this->supplierId);
        }

        return $query;
    }

    public function getData(): Collection
    {
        return $this->query()->get()->map(function (Product $product) {
            return [
                'Name' => $product->name,
                'Category' => $product->category?->name,
                'Supplier' => $product->supplier?->name,
                'Unit' => $product->unit?->label(),
                'Price' => number_format($product->price, 2),
                'Current Stock' => $product->inventory?->quantity ?? 0,
                'Total Sold' => $product->saleItems()->sum('quantity'),
            ];
        });
    }

    public function getHeaders(): array
    {
        return ['Name', 'Category', 'Supplier', 'Unit', 'Price', 'Current Stock', 'Total Sold'];
    }

    public function getFilename(): string
    {
        return 'products-report-'.now()->format('Y-m-d-His').'.csv';
    }
}
