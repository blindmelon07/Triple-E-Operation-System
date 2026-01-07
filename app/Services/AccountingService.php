<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\MaintenanceRecord;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AccountingService
{
    protected ?Carbon $startDate = null;

    protected ?Carbon $endDate = null;

    /**
     * Set the date range for calculations.
     */
    public function setDateRange(?Carbon $startDate, ?Carbon $endDate): self
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Set date range for current month.
     */
    public function forCurrentMonth(): self
    {
        $this->startDate = now()->startOfMonth();
        $this->endDate = now()->endOfMonth();

        return $this;
    }

    /**
     * Set date range for current year.
     */
    public function forCurrentYear(): self
    {
        $this->startDate = now()->startOfYear();
        $this->endDate = now()->endOfYear();

        return $this;
    }

    /**
     * Set date range for last month.
     */
    public function forLastMonth(): self
    {
        $this->startDate = now()->subMonth()->startOfMonth();
        $this->endDate = now()->subMonth()->endOfMonth();

        return $this;
    }

    /**
     * Set date range for custom period.
     */
    public function forPeriod(string $period): self
    {
        return match ($period) {
            'today' => $this->setDateRange(now()->startOfDay(), now()->endOfDay()),
            'yesterday' => $this->setDateRange(now()->subDay()->startOfDay(), now()->subDay()->endOfDay()),
            'this_week' => $this->setDateRange(now()->startOfWeek(), now()->endOfWeek()),
            'last_week' => $this->setDateRange(now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()),
            'this_month' => $this->forCurrentMonth(),
            'last_month' => $this->forLastMonth(),
            'this_quarter' => $this->setDateRange(now()->startOfQuarter(), now()->endOfQuarter()),
            'last_quarter' => $this->setDateRange(now()->subQuarter()->startOfQuarter(), now()->subQuarter()->endOfQuarter()),
            'this_year' => $this->forCurrentYear(),
            'last_year' => $this->setDateRange(now()->subYear()->startOfYear(), now()->subYear()->endOfYear()),
            default => $this->forCurrentMonth(),
        };
    }

    /**
     * Get total sales revenue.
     */
    public function getTotalRevenue(): float
    {
        $query = Sale::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        return (float) $query->sum('total');
    }

    /**
     * Get total amount collected from sales.
     */
    public function getTotalCollections(): float
    {
        $query = Sale::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        return (float) $query->sum('amount_paid');
    }

    /**
     * Get total accounts receivable (unpaid sales).
     */
    public function getAccountsReceivable(): float
    {
        return (float) Sale::query()
            ->whereIn('payment_status', ['pending', 'partial'])
            ->selectRaw('SUM(total - amount_paid) as receivable')
            ->value('receivable') ?? 0;
    }

    /**
     * Get cost of goods sold (purchase cost of items sold).
     */
    public function getCostOfGoodsSold(): float
    {
        $query = Sale::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        // Get sale IDs within the period
        $saleIds = $query->pluck('id');

        // Calculate COGS from sale items
        // This uses the product's cost price if available, otherwise uses purchase price
        return (float) SaleItem::whereIn('sale_id', $saleIds)
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('SUM(sale_items.quantity * COALESCE(products.cost_price, products.price * 0.7)) as cogs')
            ->value('cogs') ?? 0;
    }

    /**
     * Get total purchases (inventory cost).
     */
    public function getTotalPurchases(): float
    {
        $query = Purchase::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('date', [$this->startDate, $this->endDate]);
        }

        return (float) $query->sum('total');
    }

    /**
     * Get total accounts payable (unpaid purchases).
     */
    public function getAccountsPayable(): float
    {
        return (float) Purchase::query()
            ->whereIn('payment_status', ['pending', 'partial'])
            ->selectRaw('SUM(total - amount_paid) as payable')
            ->value('payable') ?? 0;
    }

    /**
     * Get total operating expenses.
     */
    public function getTotalExpenses(): float
    {
        $query = Expense::query()->where('status', 'approved');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('expense_date', [$this->startDate, $this->endDate]);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get expenses by category.
     *
     * @return Collection<int, object>
     */
    public function getExpensesByCategory(): Collection
    {
        $query = Expense::query()
            ->where('status', 'approved')
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->selectRaw('expense_categories.name as category, SUM(expenses.amount) as total')
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc('total');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('expense_date', [$this->startDate, $this->endDate]);
        }

        return $query->get();
    }

    /**
     * Get total maintenance costs.
     */
    public function getTotalMaintenanceCosts(): float
    {
        $query = MaintenanceRecord::query()->where('status', 'completed');

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('maintenance_date', [$this->startDate, $this->endDate]);
        }

        return (float) $query->sum('cost');
    }

    /**
     * Get gross profit (Revenue - COGS).
     */
    public function getGrossProfit(): float
    {
        return $this->getTotalRevenue() - $this->getCostOfGoodsSold();
    }

    /**
     * Get gross profit margin percentage.
     */
    public function getGrossProfitMargin(): float
    {
        $revenue = $this->getTotalRevenue();

        if ($revenue == 0) {
            return 0;
        }

        return ($this->getGrossProfit() / $revenue) * 100;
    }

    /**
     * Get total operating costs (expenses + maintenance).
     */
    public function getTotalOperatingCosts(): float
    {
        return $this->getTotalExpenses() + $this->getTotalMaintenanceCosts();
    }

    /**
     * Get operating profit (Gross Profit - Operating Costs).
     */
    public function getOperatingProfit(): float
    {
        return $this->getGrossProfit() - $this->getTotalOperatingCosts();
    }

    /**
     * Get operating profit margin percentage.
     */
    public function getOperatingProfitMargin(): float
    {
        $revenue = $this->getTotalRevenue();

        if ($revenue == 0) {
            return 0;
        }

        return ($this->getOperatingProfit() / $revenue) * 100;
    }

    /**
     * Get net profit (final profit after all costs).
     */
    public function getNetProfit(): float
    {
        return $this->getOperatingProfit();
    }

    /**
     * Get net profit margin percentage.
     */
    public function getNetProfitMargin(): float
    {
        return $this->getOperatingProfitMargin();
    }

    /**
     * Check if business is profitable.
     */
    public function isProfitable(): bool
    {
        return $this->getNetProfit() > 0;
    }

    /**
     * Get complete profit & loss statement.
     *
     * @return array<string, mixed>
     */
    public function getProfitAndLossStatement(): array
    {
        $revenue = $this->getTotalRevenue();
        $cogs = $this->getCostOfGoodsSold();
        $grossProfit = $revenue - $cogs;
        $expenses = $this->getTotalExpenses();
        $maintenanceCosts = $this->getTotalMaintenanceCosts();
        $totalOperatingCosts = $expenses + $maintenanceCosts;
        $operatingProfit = $grossProfit - $totalOperatingCosts;

        return [
            'period' => [
                'start' => $this->startDate?->format('Y-m-d'),
                'end' => $this->endDate?->format('Y-m-d'),
            ],
            'revenue' => [
                'sales' => $revenue,
                'total' => $revenue,
            ],
            'cost_of_goods_sold' => [
                'purchases' => $cogs,
                'total' => $cogs,
            ],
            'gross_profit' => $grossProfit,
            'gross_profit_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0,
            'operating_expenses' => [
                'general_expenses' => $expenses,
                'maintenance' => $maintenanceCosts,
                'total' => $totalOperatingCosts,
            ],
            'operating_profit' => $operatingProfit,
            'operating_profit_margin' => $revenue > 0 ? ($operatingProfit / $revenue) * 100 : 0,
            'net_profit' => $operatingProfit,
            'net_profit_margin' => $revenue > 0 ? ($operatingProfit / $revenue) * 100 : 0,
            'is_profitable' => $operatingProfit > 0,
        ];
    }

    /**
     * Get monthly revenue trend.
     *
     * @return Collection<int, object>
     */
    public function getMonthlyRevenueTrend(int $months = 12): Collection
    {
        return Sale::query()
            ->where('date', '>=', now()->subMonths($months)->startOfMonth())
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(total) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get monthly expense trend.
     *
     * @return Collection<int, object>
     */
    public function getMonthlyExpenseTrend(int $months = 12): Collection
    {
        return Expense::query()
            ->where('status', 'approved')
            ->where('expense_date', '>=', now()->subMonths($months)->startOfMonth())
            ->selectRaw("DATE_FORMAT(expense_date, '%Y-%m') as month, SUM(amount) as expenses")
            ->groupBy('month')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get monthly profit trend.
     *
     * @return Collection<int, object>
     */
    public function getMonthlyProfitTrend(int $months = 12): Collection
    {
        $revenues = $this->getMonthlyRevenueTrend($months)->keyBy('month');
        $expenses = $this->getMonthlyExpenseTrend($months)->keyBy('month');

        $allMonths = collect();
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $revenue = $revenues->get($month)?->revenue ?? 0;
            $expense = $expenses->get($month)?->expenses ?? 0;
            $allMonths->push((object) [
                'month' => $month,
                'revenue' => (float) $revenue,
                'expenses' => (float) $expense,
                'profit' => (float) $revenue - (float) $expense,
            ]);
        }

        return $allMonths;
    }

    /**
     * Get financial summary dashboard data.
     *
     * @return array<string, mixed>
     */
    public function getDashboardSummary(): array
    {
        return [
            'revenue' => $this->getTotalRevenue(),
            'collections' => $this->getTotalCollections(),
            'accounts_receivable' => $this->getAccountsReceivable(),
            'purchases' => $this->getTotalPurchases(),
            'accounts_payable' => $this->getAccountsPayable(),
            'expenses' => $this->getTotalExpenses(),
            'maintenance_costs' => $this->getTotalMaintenanceCosts(),
            'gross_profit' => $this->getGrossProfit(),
            'gross_profit_margin' => $this->getGrossProfitMargin(),
            'operating_profit' => $this->getOperatingProfit(),
            'operating_profit_margin' => $this->getOperatingProfitMargin(),
            'net_profit' => $this->getNetProfit(),
            'net_profit_margin' => $this->getNetProfitMargin(),
            'is_profitable' => $this->isProfitable(),
        ];
    }
}
