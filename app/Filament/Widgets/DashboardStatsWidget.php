<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Inventory;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class DashboardStatsWidget extends Widget
{
    use HasWidgetShield;
    protected string $view = 'filament.widgets.dashboard-stats-widget';

    public function getData(): array
    {
        return [
            'products_count' => Product::count(),
            'sales_count' => Sale::count(),
            'total_sales' => Sale::sum('total'),
            'purchases_count' => Purchase::count(),
            'low_stock_count' => Inventory::where('quantity', '<', 5)->count(),
        ];
    }
}
