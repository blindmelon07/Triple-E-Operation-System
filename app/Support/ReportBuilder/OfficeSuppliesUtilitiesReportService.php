<?php

namespace App\Support\ReportBuilder;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds an itemized ledger of office-supply and bills expenses (utilities —
 * water, electricity, internet — and office supplies) over an optional date
 * range, one row per expense, in date order, with a running cumulative total.
 */
class OfficeSuppliesUtilitiesReportService
{
    /**
     * Category names that fall under "office supply & bills" for this report.
     *
     * @return array<int, string>
     */
    public static function categoryNames(): array
    {
        return ['UTILITIES', 'SUPPLIES'];
    }

    /**
     * @return array<int, string>
     */
    public function categoryOptions(): array
    {
        return ExpenseCategory::whereIn('name', self::categoryNames())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return array{
     *     rows: Collection<int, array{date: string, category: string, payee: string, reference_number: string, description: string, amount: float, running_total: float}>,
     *     totals: array{amount: float},
     *     byCategory: array<string, float>,
     * }
     */
    public function build(?string $dateFrom = null, ?string $dateTo = null, ?int $categoryId = null): array
    {
        $expenses = Expense::query()
            ->with('category')
            ->whereHas('category', fn (Builder $q) => $q->whereIn('name', self::categoryNames()))
            ->when($categoryId, fn (Builder $q) => $q->where('expense_category_id', $categoryId))
            ->when($dateFrom, fn (Builder $q) => $q->whereDate('expense_date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $q) => $q->whereDate('expense_date', '<=', $dateTo))
            ->orderBy('expense_date')
            ->orderBy('id')
            ->get();

        $runningTotal = 0.0;
        $byCategory = [];

        $rows = $expenses->map(function (Expense $expense) use (&$runningTotal, &$byCategory) {
            $amount = (float) $expense->amount;
            $runningTotal += $amount;

            $category = $expense->category?->name ?? '';
            $byCategory[$category] = ($byCategory[$category] ?? 0) + $amount;

            return [
                'date' => optional($expense->expense_date)->format('n/j/Y') ?? '',
                'category' => $category,
                'payee' => $expense->payee ?? '',
                'reference_number' => $expense->reference_number ?? '',
                'description' => $expense->description ?? '',
                'amount' => $amount,
                'running_total' => $runningTotal,
            ];
        });

        return [
            'rows' => $rows,
            'totals' => [
                'amount' => $runningTotal,
            ],
            'byCategory' => $byCategory,
        ];
    }
}
