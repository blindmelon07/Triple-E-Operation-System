<?php

namespace App\Filament\Widgets;

use App\Services\AccountingService;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverviewWidget extends BaseWidget
{


    use HasWidgetShield;
    protected ?string $heading = 'Financial Overview';

    protected ?string $description = 'Monthly financial metrics compared to last month';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $accounting = (new AccountingService)->forCurrentMonth();
        $lastMonth = (new AccountingService)->forLastMonth();

        $currentRevenue = $accounting->getTotalRevenue();
        $lastRevenue = $lastMonth->getTotalRevenue();
        $revenueChange = $lastRevenue > 0 ? (($currentRevenue - $lastRevenue) / $lastRevenue) * 100 : 0;

        $currentProfit = $accounting->getNetProfit();
        $lastProfit = $lastMonth->getNetProfit();
        $profitChange = $lastProfit != 0 ? (($currentProfit - $lastProfit) / abs($lastProfit)) * 100 : 0;

        $currentExpenses = $accounting->getTotalOperatingCosts();
        $lastExpenses = $lastMonth->getTotalOperatingCosts();
        $expenseChange = $lastExpenses > 0 ? (($currentExpenses - $lastExpenses) / $lastExpenses) * 100 : 0;

        return [
            Stat::make('Monthly Revenue', '₱'.number_format($currentRevenue, 2))
                ->description($revenueChange >= 0 ? number_format($revenueChange, 1).'% increase' : number_format(abs($revenueChange), 1).'% decrease')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart($this->getRevenueChartData()),

            Stat::make('Monthly Profit', '₱'.number_format(abs($currentProfit), 2).($currentProfit < 0 ? ' (Loss)' : ''))
                ->description($profitChange >= 0 ? number_format($profitChange, 1).'% increase' : number_format(abs($profitChange), 1).'% decrease')
                ->descriptionIcon($profitChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($currentProfit >= 0 ? 'success' : 'danger')
                ->chart($this->getProfitChartData()),

            Stat::make('Monthly Expenses', '₱'.number_format($currentExpenses, 2))
                ->description($expenseChange <= 0 ? number_format(abs($expenseChange), 1).'% decrease' : number_format($expenseChange, 1).'% increase')
                ->descriptionIcon($expenseChange <= 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-arrow-trending-up')
                ->color($expenseChange <= 0 ? 'success' : 'warning')
                ->chart($this->getExpenseChartData()),

            Stat::make('Profit Margin', number_format($accounting->getNetProfitMargin(), 1).'%')
                ->description($accounting->isProfitable() ? 'Business is profitable' : 'Business is at a loss')
                ->descriptionIcon($accounting->isProfitable() ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                ->color($accounting->isProfitable() ? 'success' : 'danger'),
        ];
    }

    /**
     * @return array<int>
     */
    protected function getRevenueChartData(): array
    {
        $accounting = new AccountingService;
        $trend = $accounting->getMonthlyRevenueTrend(6);

        return $trend->pluck('revenue')->map(fn ($value) => (int) $value)->toArray();
    }

    /**
     * @return array<int>
     */
    protected function getProfitChartData(): array
    {
        $accounting = new AccountingService;
        $trend = $accounting->getMonthlyProfitTrend(6);

        return $trend->pluck('profit')->map(fn ($value) => (int) $value)->toArray();
    }

    /**
     * @return array<int>
     */
    protected function getExpenseChartData(): array
    {
        $accounting = new AccountingService;
        $trend = $accounting->getMonthlyExpenseTrend(6);

        return $trend->pluck('expenses')->map(fn ($value) => (int) $value)->toArray();
    }
}
