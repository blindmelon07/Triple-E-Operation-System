<?php

namespace App\Support\ReportBuilder;

use App\Models\CashAdvance;
use App\Models\CashAdvancePayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Cash advance monitoring sheet over an optional date range.
 *
 * For each employee: a beginning balance (everything granted minus
 * everything repaid before dateFrom), the advances and repayments inside
 * the period, and the ending balance. Two shapes come out of one pass:
 *
 *  - summary: one row per employee (beginning / advances / deductions / ending)
 *  - ledgers: per employee, a dated transaction list with a running balance
 *
 * Repayments from cancelled payrolls are excluded (CashAdvancePayment::effective).
 * Activity after dateTo is ignored entirely. Employees with no activity in
 * the period and a zero balance are left out.
 */
class CashAdvanceReportService
{
    /**
     * @return array{
     *     summary: Collection<int, array{employee: string, beginning: float, advances: float, deductions: float, ending: float}>,
     *     summaryTotals: array{beginning: float, advances: float, deductions: float, ending: float},
     *     ledgers: Collection<int, array{employee: string, beginning: float, rows: array<int, array{date: string, reference: string, particulars: string, advance: float, deduction: float, balance: float}>, totals: array{advances: float, deductions: float, ending: float}}>,
     * }
     */
    public function build(?string $dateFrom = null, ?string $dateTo = null, ?int $employeeId = null): array
    {
        $advances = CashAdvance::query()
            ->with([
                'employee',
                'payments' => fn ($q) => $q->effective()
                    ->when($dateTo, fn (Builder $q) => $q->whereDate('payment_date', '<=', $dateTo))
                    ->with('payrollItem.payroll'),
            ])
            ->when($employeeId, fn (Builder $q) => $q->where('employee_id', $employeeId))
            ->when($dateTo, fn (Builder $q) => $q->whereDate('date_granted', '<=', $dateTo))
            ->get();

        $ledgers = $advances
            ->groupBy('employee_id')
            ->map(fn (Collection $employeeAdvances) => $this->ledgerFor($employeeAdvances, $dateFrom))
            ->filter(fn (array $l) => ! empty($l['rows']) || round($l['beginning'], 2) != 0.0)
            ->sortBy('employee', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $summary = $ledgers->map(fn (array $l) => [
            'employee' => $l['employee'],
            'beginning' => $l['beginning'],
            'advances' => $l['totals']['advances'],
            'deductions' => $l['totals']['deductions'],
            'ending' => $l['totals']['ending'],
        ]);

        return [
            'summary' => $summary,
            'summaryTotals' => [
                'beginning' => round($summary->sum('beginning'), 2),
                'advances' => round($summary->sum('advances'), 2),
                'deductions' => round($summary->sum('deductions'), 2),
                'ending' => round($summary->sum('ending'), 2),
            ],
            'ledgers' => $ledgers,
        ];
    }

    /**
     * @param  Collection<int, CashAdvance>  $advances  all for one employee
     * @return array{employee: string, beginning: float, rows: array<int, array{date: string, reference: string, particulars: string, advance: float, deduction: float, balance: float}>, totals: array{advances: float, deductions: float, ending: float}}
     */
    private function ledgerFor(Collection $advances, ?string $dateFrom): array
    {
        $beginning = 0.0;
        $entries = [];

        foreach ($advances as $advance) {
            $granted = $advance->date_granted->toDateString();

            if ($dateFrom && $granted < $dateFrom) {
                $beginning += (float) $advance->amount;
            } else {
                $entries[] = [
                    'sort' => [$granted, 0, $advance->id],
                    'date' => $granted,
                    'reference' => $advance->reference_number ?? '',
                    'particulars' => trim('Cash advance'.($advance->purpose ? ' — '.$advance->purpose : '')),
                    'advance' => (float) $advance->amount,
                    'deduction' => 0.0,
                ];
            }

            /** @var CashAdvancePayment $payment */
            foreach ($advance->payments as $payment) {
                $paid = $payment->payment_date->toDateString();

                if ($dateFrom && $paid < $dateFrom) {
                    $beginning -= (float) $payment->amount;

                    continue;
                }

                $payroll = $payment->payrollItem?->payroll;

                $entries[] = [
                    // Same-day: advance first, then repayments, so the
                    // running balance never dips below zero mid-day.
                    'sort' => [$paid, 1, $payment->id],
                    'date' => $paid,
                    'reference' => $advance->reference_number ?? '',
                    'particulars' => $payroll
                        ? "Payroll deduction — {$payroll->payroll_number}"
                        : trim('Repayment'.($payment->notes ? ' — '.$payment->notes : '')),
                    'advance' => 0.0,
                    'deduction' => (float) $payment->amount,
                ];
            }
        }

        usort($entries, fn (array $a, array $b) => $a['sort'] <=> $b['sort']);

        $beginning = round($beginning, 2);
        $running = $beginning;
        $rows = [];

        foreach ($entries as $e) {
            $running = round($running + $e['advance'] - $e['deduction'], 2);

            $rows[] = [
                'date' => date('n/j/Y', strtotime($e['date'])),
                'reference' => $e['reference'],
                'particulars' => $e['particulars'],
                'advance' => $e['advance'],
                'deduction' => $e['deduction'],
                'balance' => $running,
            ];
        }

        return [
            'employee' => $advances->first()->employee?->name ?? 'Unknown employee',
            'beginning' => $beginning,
            'rows' => $rows,
            'totals' => [
                'advances' => round(array_sum(array_column($rows, 'advance')), 2),
                'deductions' => round(array_sum(array_column($rows, 'deduction')), 2),
                'ending' => $running,
            ],
        ];
    }
}
