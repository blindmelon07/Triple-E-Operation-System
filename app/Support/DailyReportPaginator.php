<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Splits the Daily/Period Transaction Report's growing lists (named-customer
 * sales, walk-in sales, expenses) into page-sized chunks before they ever
 * reach the blade view.
 *
 * Why this exists: the report's 4-column layout is a single-row table so the
 * columns sit side by side. DomPDF only knows how to break a table across
 * pages at row boundaries — when one column's content overflows the page,
 * DomPDF tries to split that single row itself and mis-renders it (an extra
 * near-blank page, followed by overlapping/garbled content once it resumes).
 * Pre-chunking here guarantees every chunk we hand to DomPDF already fits
 * one page, so it never has to invoke that broken row-splitting path.
 *
 * ROW_BUDGET counts each item/expense line as 1 row regardless of how many
 * visual lines it wraps to. That sounds naive, but an earlier version tried
 * to estimate wrapped line counts from character length and it was *worse*:
 * getting the per-column chars-per-line guess even slightly wrong silently
 * reintroduced the exact overflow bug this class exists to prevent. A flat,
 * deliberately conservative budget (tuned against real multi-item orders —
 * see the calibration notes below) is the version that actually held up
 * under stress testing with long descriptions and 6-9 item orders. It costs
 * a few extra pages on a busy day; that's a fair trade for a report that
 * never renders corrupted.
 */
class DailyReportPaginator
{
    /**
     * Conservative per-page row budget shared by the sales/expense columns.
     * Verified empirically (see the manual repro scripts used while building
     * this) to keep every chunk within one physical A4-landscape page, even
     * for orders with 6-9 long-description items each — the DomPDF
     * row-splitting bug only reappeared above ~20 at these font sizes, so
     * this leaves real margin rather than sitting right at the edge.
     */
    public const ROW_BUDGET = 14;

    /**
     * Split a collection of sales (each with a `sale_items` relation) into
     * pages, keeping every sale block intact — a block never splits across
     * pages, it just starts the next page if it wouldn't fit the current one.
     *
     * @param  Collection  $sales
     * @return array<int, Collection>
     */
    public static function chunkSales(Collection $sales, int $rowBudget = self::ROW_BUDGET): array
    {
        $pages = [];
        $current = collect();
        $rows = 0;

        foreach ($sales as $sale) {
            $itemRows = $sale->sale_items->where('is_voided', false)->count();
            $blockRows = $itemRows + 2; // name/header row + subtotal row

            if ($rows > 0 && $rows + $blockRows > $rowBudget) {
                $pages[] = $current;
                $current = collect();
                $rows = 0;
            }

            $current->push($sale);
            $rows += $blockRows;
        }

        if ($current->isNotEmpty() || empty($pages)) {
            $pages[] = $current;
        }

        return $pages;
    }

    /**
     * Split grouped expenses (as returned by
     * POSController::groupExpensesByReportGroup) into pages. A group that
     * doesn't fit what's left of a page continues onto the next one — its
     * label is repeated with "(cont'd)" and its subtotal is shown only once,
     * on the chunk where the group actually ends.
     *
     * @param  array<string, array{label: string, items: Collection, total: float}>  $expenseGroups
     * @return array<int, array<int, array{label: string, items: Collection, total: float, show_total: bool}>>
     */
    public static function chunkExpenseGroups(array $expenseGroups, int $rowBudget = self::ROW_BUDGET): array
    {
        $pages = [];
        $current = [];
        $rows = 0;

        foreach ($expenseGroups as $group) {
            $items = $group['items'];

            if ($items->isEmpty()) {
                continue;
            }

            $remaining = $items->values();
            $isFirstChunk = true;

            while ($remaining->isNotEmpty()) {
                // Not even room for a group label + one item — start fresh.
                if ($rows > 0 && $rows + 2 > $rowBudget) {
                    $pages[] = $current;
                    $current = [];
                    $rows = 0;
                }

                $available = max(1, $rowBudget - $rows - 1); // -1 for the group label row
                $chunkItems = $remaining->take($available)->values();
                $remaining = $remaining->slice($chunkItems->count())->values();

                $current[] = [
                    'label' => $group['label'].($isFirstChunk ? '' : " (cont'd)"),
                    'items' => $chunkItems,
                    'total' => $group['total'],
                    'show_total' => $remaining->isEmpty(),
                ];
                $rows += 1 + $chunkItems->count();
                $isFirstChunk = false;
            }
        }

        if (! empty($current) || empty($pages)) {
            $pages[] = $current;
        }

        return $pages;
    }

    /**
     * Split a flat collection into pages at one row per item — for simple
     * lists like the Period Report's per-session summary table, which has
     * no sub-structure to preserve (unlike a sale block or expense group).
     *
     * @return array<int, Collection>
     */
    public static function chunkFlat(Collection $items, int $rowBudget = self::ROW_BUDGET): array
    {
        $pages = [];
        foreach ($items->values()->chunk($rowBudget) as $chunk) {
            $pages[] = $chunk->values();
        }

        return $pages ?: [collect()];
    }

    /**
     * Zip two independently-paginated tracks (e.g. the Period Report's final
     * summary page: a sessions table alongside a period-wide expenses list)
     * into one list of pages, the same way `zip()` does for three tracks.
     *
     * @param  array<int, mixed>  $trackA
     * @param  array<int, mixed>  $trackB
     * @return array<int, array{a: mixed, a_is_last: bool, b: mixed, b_is_last: bool, is_first: bool}>
     */
    public static function zipTwo(array $trackA, array $trackB): array
    {
        $pageCount = max(count($trackA), count($trackB), 1);
        $pages = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $pages[] = [
                'a' => $trackA[$i] ?? collect(),
                'a_is_last' => $i === count($trackA) - 1,
                'b' => $trackB[$i] ?? [],
                'b_is_last' => $i === count($trackB) - 1,
                'is_first' => $i === 0,
            ];
        }

        return $pages;
    }

    /**
     * Zip named-sales / walk-in-sales / expense pages into one list of
     * report pages, padding shorter tracks with empty chunks. Each entry
     * also flags whether it's that column's *last* page, so the view knows
     * where to print the column's running total/cross-check block.
     *
     * @param  array<int, Collection>  $namedPages
     * @param  array<int, Collection>  $walkinPages
     * @param  array<int, array<int, array{label: string, items: Collection, total: float, show_total: bool}>>  $expensePages
     * @return array<int, array{
     *     named: Collection, named_is_last: bool,
     *     walkin: Collection, walkin_is_last: bool,
     *     expenses: array, expenses_is_last: bool,
     *     is_first: bool,
     * }>
     */
    public static function zip(array $namedPages, array $walkinPages, array $expensePages): array
    {
        $pageCount = max(count($namedPages), count($walkinPages), count($expensePages), 1);
        $pages = [];

        for ($i = 0; $i < $pageCount; $i++) {
            $pages[] = [
                'named' => $namedPages[$i] ?? collect(),
                'named_is_last' => $i === count($namedPages) - 1,
                'walkin' => $walkinPages[$i] ?? collect(),
                'walkin_is_last' => $i === count($walkinPages) - 1,
                'expenses' => $expensePages[$i] ?? [],
                'expenses_is_last' => $i === count($expensePages) - 1,
                'is_first' => $i === 0,
            ];
        }

        return $pages;
    }
}
