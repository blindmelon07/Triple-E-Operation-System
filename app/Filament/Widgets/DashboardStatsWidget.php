<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Inventory;

class DashboardStatsWidget extends Widget
{
    protected string $view = 'filament.widgets.dashboard-stats-widget';

    public function getData(): array
    {
        return [
            'products_count' => Product::count(),
            'sales_count' => Sale::count(),
            'purchases_count' => Purchase::count(),
            'low_stock_count' => Inventory::where('quantity', '<', 5)->count(),
        ];
    }
}
