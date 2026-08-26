<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Collection;

class SupplierPriceComparisonExport
{
    protected Collection $suppliers;

    public function __construct(protected ?int $categoryId = null)
    {
        $this->suppliers = Supplier::query()
            ->whereHas('productPrices')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Product::query()
            ->whereHas('supplierPrices')
            ->with(['category', 'supplierPrices'])
            ->when($this->categoryId, fn ($query) => $query->where('category_id', $this->categoryId))
            ->orderBy('name');
    }

    /**
     * One row per product: Product, Category, then one column per supplier
     * (formatted price, or blank if that supplier has no price on record).
     *
     * @return Collection<int, array<string, string|null>>
     */
    public function getData(): Collection
    {
        return $this->query()->get()->map(function (Product $product) {
            $prices = $product->supplierPrices->keyBy('supplier_id');

            $row = [
                'Product' => $product->name,
                'Category' => $product->category?->name,
            ];

            foreach ($this->suppliers as $supplier) {
                $price = $prices->get($supplier->id);
                $row[$supplier->name] = $price ? number_format((float) $price->base_price, 2) : '';
            }

            return $row;
        });
    }

    public function getHeaders(): array
    {
        return array_merge(['Product', 'Category'], $this->suppliers->pluck('name')->all());
    }

    public function getSuppliers(): Collection
    {
        return $this->suppliers;
    }

    public function getFilename(): string
    {
        return 'supplier-price-comparison-'.now()->format('Y-m-d-His').'.csv';
    }
}
