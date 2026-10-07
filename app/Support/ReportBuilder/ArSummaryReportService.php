<?php

namespace App\Support\ReportBuilder;

use App\Models\Customer;
use App\Models\Sale;

/**
 * Statement of Account summary — one row per customer who still owes money,
 * with their latest SOA number / billing date / notes, the outstanding
 * balance, a running total down the sheet, and DUE / OVER DUE status
 * (OVER DUE when any unpaid invoice is past its due date).
 */
class ArSummaryReportService
{
    /**
     * @return array{rows: array<int, array{id: int, name: string, soa_number: ?string, billing_date: ?string, receivable: float, running_total: float, status: string, notes: ?string}>, total: float}
     */
    public function build(?int $customerId = null, ?string $status = null): array
    {
        $today = now()->startOfDay();

        $balances = Sale::query()
            ->where('payment_status', '!=', 'paid')
            ->where('is_voided', false)
            ->whereNotNull('customer_id')
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))
            ->get(['customer_id', 'total', 'amount_paid', 'due_date'])
            ->groupBy('customer_id')
            ->map(fn ($sales) => [
                'receivable' => round($sales->sum(fn (Sale $s) => $s->balance), 2),
                'overdue' => $sales->contains(fn (Sale $s) => $s->balance > 0.009 && $s->due_date && $s->due_date->lt($today)),
            ])
            ->filter(fn ($b) => $b['receivable'] > 0.009);

        $customers = Customer::whereIn('id', $balances->keys())->orderBy('id')->get();

        $rows = [];
        $running = 0.0;

        foreach ($customers as $customer) {
            $balance = $balances[$customer->id];
            $rowStatus = $balance['overdue'] ? 'OVER DUE' : 'DUE';

            if ($status && $rowStatus !== $status) {
                continue;
            }

            $running += $balance['receivable'];

            $rows[] = [
                'id' => $customer->id,
                'name' => $customer->name,
                'soa_number' => $customer->soa_number,
                'billing_date' => $customer->soa_billing_date?->toDateString(),
                'receivable' => $balance['receivable'],
                'running_total' => round($running, 2),
                'status' => $rowStatus,
                'notes' => $customer->soa_notes,
            ];
        }

        return ['rows' => $rows, 'total' => round($running, 2)];
    }
}
