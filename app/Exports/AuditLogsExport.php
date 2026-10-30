<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class AuditLogsExport
{
    public function __construct(
        protected ?int $userId = null,
        protected ?string $dateFrom = null,
        protected ?string $dateUntil = null,
        protected bool $summary = false,
    ) {}

    public function query(): Builder
    {
        return AuditLog::query()
            ->when($this->userId, fn (Builder $q, int $id) => $q->where('user_id', $id))
            ->when($this->dateFrom, fn (Builder $q, string $date) => $q->whereDate('created_at', '>=', $date))
            ->when($this->dateUntil, fn (Builder $q, string $date) => $q->whereDate('created_at', '<=', $date));
    }

    public function getData(): iterable
    {
        return $this->summary ? $this->getSummaryData() : $this->getDetailedData();
    }

    /**
     * Every log entry, oldest first. Streamed in chunks — the audit log has
     * tens of thousands of rows.
     */
    protected function getDetailedData(): LazyCollection
    {
        return $this->query()
            ->with('user')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lazy(500)
            ->map(fn (AuditLog $log) => [
                'Timestamp' => $log->created_at?->format('Y-m-d H:i:s'),
                'User' => $log->user_display_name,
                'Action' => str_replace('_', ' ', ucfirst($log->action)),
                'Model' => $log->auditable_type ? class_basename($log->auditable_type) : '',
                'Record' => $log->auditable_label,
                'Changes' => $this->describeChanges($log),
                'IP Address' => $log->ip_address,
                'Device' => $log->device,
                'Browser (raw)' => $log->user_agent,
            ]);
    }

    protected const VOID_REQUESTS = ['requested_void', 'requested_item_void', 'requested_item_exchange', 'cancelled_void_request'];

    protected const VOID_DECISIONS = ['approved_void_request', 'approved_item_void_request', 'approved_item_exchange_request', 'rejected_void_request'];

    /**
     * One row per user per day: when they started and stopped, how much they
     * sold, collected and spent, what the register did, which records they
     * touched (by type), which records they deleted, and where they worked from.
     *
     * Built in PHP rather than SQL because the amounts live in the JSON
     * new_values column.
     */
    protected function getSummaryData(): Collection
    {
        $groups = [];
        $total = $this->emptyTally();

        $this->query()
            ->select(['id', 'user_id', 'user_name', 'action', 'auditable_type', 'auditable_label', 'new_values', 'ip_address', 'user_agent', 'created_at'])
            ->with('user:id,name')
            ->orderBy('created_at')
            ->orderBy('id')
            ->lazy(1000)
            ->each(function (AuditLog $log) use (&$groups, &$total) {
                $day = $log->created_at->toDateString();
                $key = $day.'|'.$log->user_id;

                $groups[$key] ??= ['day' => $day, 'user' => $log->user_display_name] + $this->emptyTally();

                $this->tally($groups[$key], $log);
                $this->tally($total, $log);
            });

        $data = collect($groups)
            ->sortBy([['day', 'asc'], ['user', 'asc']])
            ->map(fn (array $t) => $this->summaryRow($t['day'], $t['user'], $t))
            ->values();

        $data->push(array_merge($this->summaryRow('TOTAL', '', $total), [
            'First Activity' => '',
            'Last Activity' => '',
            'Hours Active' => '',
            'Deleted Records' => '',
            'IP Addresses' => '',
            'Devices' => '',
        ]));

        return $data;
    }

    protected function emptyTally(): array
    {
        return [
            'first' => null, 'last' => null, 'total' => 0,
            'logins' => 0, 'logouts' => 0, 'failed_logins' => 0,
            'sales' => 0, 'sales_amount' => 0.0, 'sales_by_method' => [],
            'payments' => 0, 'payments_amount' => 0.0,
            'expenses' => 0, 'expenses_amount' => 0.0,
            'register_opening' => 0.0, 'cash_added' => 0.0, 'register_closing' => 0.0, 'register_discrepancy' => 0.0,
            'void_requests' => 0, 'void_decisions' => 0, 'prints' => 0,
            'created' => [], 'updated' => [], 'deleted' => [], 'deleted_labels' => [],
            'other' => [], 'ips' => [], 'devices' => [],
        ];
    }

    protected function tally(array &$t, AuditLog $log): void
    {
        $new = $log->new_values ?? [];
        $model = $log->auditable_type ? class_basename($log->auditable_type) : 'Other';
        $amount = fn (string $key) => (float) ($new[$key] ?? 0);
        $bump = function (string $bucket, string $key, int|float $by = 1) use (&$t) {
            $t[$bucket][$key] = ($t[$bucket][$key] ?? 0) + $by;
        };

        $t['total']++;
        $t['first'] ??= $log->created_at;
        $t['last'] = $log->created_at;

        if ($log->ip_address) {
            $t['ips'][$log->ip_address] = true;
        }
        if ($log->user_agent) {
            $t['devices'][$log->device] = true;
        }

        switch (true) {
            case $log->action === 'login':
                $t['logins']++;
                break;
            case $log->action === 'logout':
                $t['logouts']++;
                break;
            case $log->action === 'login_failed':
                $t['failed_logins']++;
                break;
            case $log->action === 'completed_sale':
                $method = $new['payment_method'] ?? 'unknown';
                $t['sales']++;
                $t['sales_amount'] += $amount('total');
                $t['sales_by_method'][$method]['count'] = ($t['sales_by_method'][$method]['count'] ?? 0) + 1;
                $t['sales_by_method'][$method]['amount'] = ($t['sales_by_method'][$method]['amount'] ?? 0) + $amount('total');
                break;
            case $log->action === 'settled_invoice_payment':
                $t['payments']++;
                $t['payments_amount'] += $amount('amount_paid_now');
                break;
            case $log->action === 'opened_register':
                $t['register_opening'] += $amount('opening_amount');
                break;
            case $log->action === 'added_cash_to_register':
                $t['cash_added'] += $amount('amount');
                break;
            case $log->action === 'closed_register':
                $t['register_closing'] += $amount('closing_amount');
                $t['register_discrepancy'] += $amount('discrepancy');
                break;
            case in_array($log->action, self::VOID_REQUESTS, true):
                $t['void_requests']++;
                break;
            case in_array($log->action, self::VOID_DECISIONS, true):
                $t['void_decisions']++;
                break;
            case $log->action === 'printed_receipt':
                $t['prints']++;
                break;
            case $log->action === 'created':
                $bump('created', $model);
                // Every expense gets a model "created" entry, but only POS
                // expenses also get "created_expense" — so count them here.
                if ($model === 'Expense') {
                    $t['expenses']++;
                    $t['expenses_amount'] += $amount('amount');
                }
                break;
            case $log->action === 'updated':
                $bump('updated', $model);
                break;
            case $log->action === 'deleted':
                $bump('deleted', $model);
                $t['deleted_labels'][] = $log->auditable_label ?: $model;
                break;
            case $log->action === 'created_expense':
                break; // already counted via the model "created" entry
            default:
                $bump('other', str_replace('_', ' ', $log->action));
        }
    }

    protected function summaryRow(string $date, string $user, array $t): array
    {
        $breakdown = fn (array $counts) => collect($counts)->sortDesc()->map(fn ($n, $k) => "{$k}: {$n}")->implode('; ');
        $minutes = $t['first'] ? (int) $t['first']->diffInMinutes($t['last']) : 0;

        return [
            'Date' => $date,
            'User' => $user,
            'First Activity' => $t['first']?->format('H:i:s') ?? '',
            'Last Activity' => $t['last']?->format('H:i:s') ?? '',
            'Hours Active' => sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60),
            'Total Actions' => $t['total'],
            'Logins' => $t['logins'],
            'Logouts' => $t['logouts'],
            'Failed Logins' => $t['failed_logins'],
            'Sales' => $t['sales'],
            'Sales Amount' => round($t['sales_amount'], 2),
            'Sales by Payment Method' => collect($t['sales_by_method'])
                ->sortByDesc('amount')
                ->map(fn (array $m, string $method) => "{$method}: {$m['count']} (".number_format($m['amount'], 2).')')
                ->implode('; '),
            'Payments Collected' => $t['payments'],
            'Payments Collected Amount' => round($t['payments_amount'], 2),
            'Expenses' => $t['expenses'],
            'Expenses Amount' => round($t['expenses_amount'], 2),
            'Register Opening' => round($t['register_opening'], 2),
            'Cash Added to Register' => round($t['cash_added'], 2),
            'Register Closing' => round($t['register_closing'], 2),
            'Register Discrepancy' => round($t['register_discrepancy'], 2),
            'Void/Exchange Requests' => $t['void_requests'],
            'Void Approvals/Rejections' => $t['void_decisions'],
            'Receipt Prints' => $t['prints'],
            'Records Created' => array_sum($t['created']),
            'Created by Type' => $breakdown($t['created']),
            'Records Updated' => array_sum($t['updated']),
            'Updated by Type' => $breakdown($t['updated']),
            'Records Deleted' => array_sum($t['deleted']),
            'Deleted by Type' => $breakdown($t['deleted']),
            'Deleted Records' => implode(', ', $t['deleted_labels']),
            'Other Actions' => $breakdown($t['other']),
            'IP Addresses' => collect(array_keys($t['ips']))->sort()->implode(', '),
            'Devices' => implode(', ', array_keys($t['devices'])),
        ];
    }

    /**
     * "field: old → new" for edits, "field: value" for everything else.
     */
    protected function describeChanges(AuditLog $log): string
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        $fields = array_unique([...array_keys($new), ...array_keys($old)]);

        return collect($fields)
            ->map(function (string $field) use ($old, $new) {
                if (array_key_exists($field, $old) && array_key_exists($field, $new)) {
                    return "{$field}: {$this->format($old[$field])} → {$this->format($new[$field])}";
                }

                return array_key_exists($field, $new)
                    ? "{$field}: {$this->format($new[$field])}"
                    : "{$field}: {$this->format($old[$field])} (removed)";
            })
            ->implode('; ');
    }

    protected function format(mixed $value): string
    {
        return match (true) {
            $value === null => '(empty)',
            is_bool($value) => $value ? 'yes' : 'no',
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }

    public function getHeaders(): array
    {
        if ($this->summary) {
            return array_keys($this->summaryRow('', '', $this->emptyTally()));
        }

        return ['Timestamp', 'User', 'Action', 'Model', 'Record', 'Changes', 'IP Address', 'Device', 'Browser (raw)'];
    }

    public function getFilename(): string
    {
        $parts = ['audit-logs', $this->summary ? 'summary' : 'detailed'];

        if ($this->userId) {
            $parts[] = 'user-'.$this->userId;
        }
        if ($this->dateFrom || $this->dateUntil) {
            $parts[] = ($this->dateFrom ?? 'start').'-to-'.($this->dateUntil ?? 'today');
        }

        return implode('-', $parts).'-'.now()->format('Y-m-d-His').'.csv';
    }
}
