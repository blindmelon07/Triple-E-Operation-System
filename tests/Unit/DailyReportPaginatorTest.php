<?php

use App\Support\DailyReportPaginator;
use Illuminate\Support\Collection;

/**
 * Regression tests for the pagination that fixes the Daily Transaction
 * Report blank/garbled-page bug: DomPDF mis-renders a table row that's
 * taller than one page, so every chunk handed to the view must already fit
 * within DailyReportPaginator::ROW_BUDGET.
 */
function fakeSale(?string $customerName, array $itemCounts = [1]): object
{
    $sale = new stdClass;
    $sale->customer_id = $customerName ? 1 : null;
    $sale->customer = $customerName ? (object) ['name' => $customerName, 'address' => null] : null;
    $sale->reference_number = null;
    $sale->total = 0;
    $sale->payment_status = 'paid';

    $items = [];
    foreach ($itemCounts as $count) {
        for ($i = 0; $i < $count; $i++) {
            $items[] = (object) ['is_voided' => false];
        }
    }
    $sale->sale_items = collect($items);

    return $sale;
}

function fakeExpense(string $description, float $amount = 100): object
{
    return (object) ['description' => $description, 'category' => null, 'payee' => null, 'amount' => $amount];
}

test('a single sale that fits the budget stays on one page', function () {
    $sales = collect([fakeSale('Customer A', [3])]);

    $pages = DailyReportPaginator::chunkSales($sales, rowBudget: 10);

    expect($pages)->toHaveCount(1)
        ->and($pages[0])->toHaveCount(1);
});

test('sales are split across pages once the row budget is exceeded, without splitting a sale block', function () {
    // Each sale costs 1 (header) + N (items) + 1 (subtotal) rows.
    $sales = collect([
        fakeSale('Customer A', [3]), // 5 rows
        fakeSale('Customer B', [3]), // 5 rows -> 10 total, still fits budget 10
        fakeSale('Customer C', [3]), // would push to 15 -> must start page 2
    ]);

    $pages = DailyReportPaginator::chunkSales($sales, rowBudget: 10);

    expect($pages)->toHaveCount(2);
    expect($pages[0])->toHaveCount(2);
    expect($pages[1])->toHaveCount(1);

    // No sale is duplicated or dropped across the split.
    $allSales = collect($pages)->flatMap(fn (Collection $page) => $page);
    expect($allSales->pluck('customer.name')->all())
        ->toBe(['Customer A', 'Customer B', 'Customer C']);
});

test('a single oversized sale block is never split — it just gets its own page', function () {
    $sales = collect([
        fakeSale('Small Customer', [1]), // 3 rows
        fakeSale('Huge Customer', [50]), // 52 rows, bigger than any reasonable budget
    ]);

    $pages = DailyReportPaginator::chunkSales($sales, rowBudget: 10);

    expect($pages)->toHaveCount(2);
    expect($pages[0]->first()->customer->name)->toBe('Small Customer');
    expect($pages[1]->first()->customer->name)->toBe('Huge Customer');
    // The huge sale's items are all still present — never truncated.
    expect($pages[1]->first()->sale_items)->toHaveCount(50);
});

test('an empty sales collection still produces exactly one (empty) page', function () {
    $pages = DailyReportPaginator::chunkSales(collect(), rowBudget: 10);

    expect($pages)->toHaveCount(1);
    expect($pages[0])->toBeEmpty();
});

test('expense groups continue onto a new page with a "cont\'d" label and a single subtotal', function () {
    $items = collect(range(1, 8))->map(fn ($i) => fakeExpense("Expense $i"));
    $groups = [
        ['label' => 'Others', 'items' => $items, 'total' => (float) $items->sum('amount')],
    ];

    $pages = DailyReportPaginator::chunkExpenseGroups($groups, rowBudget: 5);

    expect(count($pages))->toBeGreaterThan(1);

    $chunks = collect($pages)->flatMap(fn ($page) => $page);
    expect($chunks->first()['label'])->toBe('Others');
    expect($chunks->skip(1)->first()['label'])->toBe("Others (cont'd)");

    // Exactly one chunk shows the subtotal, and only once all items are accounted for.
    expect($chunks->where('show_total', true))->toHaveCount(1);
    $totalItemsShown = $chunks->sum(fn ($chunk) => $chunk['items']->count());
    expect($totalItemsShown)->toBe(8);
});

test('zip pads shorter tracks and flags each track\'s last page correctly', function () {
    $named = [collect(['n1']), collect(['n2'])];
    $walkin = [collect(['w1'])];
    $expenses = [[], [], []];

    $pages = DailyReportPaginator::zip($named, $walkin, $expenses);

    expect($pages)->toHaveCount(3); // max(2, 1, 3)

    expect($pages[0]['named_is_last'])->toBeFalse();
    expect($pages[1]['named_is_last'])->toBeTrue();
    expect($pages[0]['walkin_is_last'])->toBeTrue(); // walkin only had 1 page
    expect($pages[1]['walkin'])->toBeInstanceOf(Collection::class)->toBeEmpty();
    expect($pages[2]['expenses_is_last'])->toBeTrue();
    expect($pages[0]['is_first'])->toBeTrue();
    expect($pages[1]['is_first'])->toBeFalse();
});

test('the production row budget keeps a realistically large day within a sane page count', function () {
    // 40 named sales x 2 items each — the exact shape that triggered the
    // original DomPDF blank/garbled-page bug reported in production.
    $sales = collect(range(1, 40))->map(fn ($i) => fakeSale("Customer $i", [2]));

    $pages = DailyReportPaginator::chunkSales($sales);

    // Every chunk must respect the budget (this is what keeps DomPDF from
    // ever needing to split a table row across pages).
    foreach ($pages as $page) {
        $rows = $page->sum(fn ($sale) => $sale->sale_items->where('is_voided', false)->count() + 2);
        expect($rows)->toBeLessThanOrEqual(DailyReportPaginator::ROW_BUDGET);
    }

    // And no sale was lost or duplicated in the process.
    expect(collect($pages)->flatMap(fn ($page) => $page))->toHaveCount(40);
});
