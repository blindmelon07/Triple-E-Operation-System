<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Inventory;

class StatsOverview extends BaseWidget
{
    protected ?string $heading = 'Store Overview';
    protected ?string $description = 'Quick stats for products, sales, purchases, and low stock.';

    protected function getStats(): array
    {
        return [
            Stat::make('Products', Product::count())
                ->description('Total products in inventory')
                ->color('primary'),
            Stat::make('Sales', Sale::count())
                ->description('Total sales made')
                ->color('success'),
                Stat::make('Total Sales Price', '₱' . number_format(Sale::sum('total'), 2))
                    ->description('Sum of all sales in PHP')
                    ->color('success'),
            Stat::make('Purchases', Purchase::count())
                ->description('Total purchases received')
                ->color('info'),
            Stat::make('Low Stock', Inventory::where('quantity', '<', 5)->count())
                ->description('Products below threshold')
                ->color('danger'),
        ];
    }
}
