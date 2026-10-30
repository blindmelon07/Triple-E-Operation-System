<?php

namespace App\Exports;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * One row per user per day: what kinds of things they did, when they
     * started and stopped, and which IP addresses they worked from.
     */
    protected function getSummaryData(): Collection
    {
        $count = fn (string $alias, array $actions) => DB::raw("SUM(CASE WHEN action IN ('".implode("','", $actions)."') THEN 1 ELSE 0 END) as {$alias}");

        $rows = $this->query()
            ->select([
                DB::raw('DATE(created_at) as day'),
                'user_id',
                DB::raw('MAX(user_name) as user_name'),
                DB::raw('COUNT(*) as total'),
                DB::raw('MIN(created_at) as first_at'),
                DB::raw('MAX(created_at) as last_at'),
            ])
            ->addSelect([
                $count('logins', ['login']),
                $count('failed_logins', ['login_failed']),
                $count('sales', ['completed_sale']),
                $count('void_requests', ['requested_void', 'requested_item_void', 'requested_item_exchange', 'cancelled_void_request']),
                $count('void_decisions', ['approved_void_request', 'approved_item_void_request', 'approved_item_exchange_request', 'rejected_void_request']),
                $count('prints', ['printed_receipt']),
                $count('created', ['created']),
                $count('updated', ['updated']),
                $count('deleted', ['deleted']),
            ])
            ->groupBy('day', 'user_id')
            ->orderBy('day')
            ->orderBy('user_name')
            ->toBase()
            ->get();

        $userNames = User::whereIn('id', $rows->pluck('user_id')->filter())->pluck('name', 'id');

        // Distinct IPs per user per day, fetched separately to stay portable
        // across MySQL and SQLite (GROUP_CONCAT syntax differs).
        $ips = $this->query()
            ->whereNotNull('ip_address')
            ->select([DB::raw('DATE(created_at) as day'), 'user_id', 'ip_address'])
            ->distinct()
            ->toBase()
            ->get()
            ->groupBy(fn ($r) => $r->day.'|'.$r->user_id)
            ->map(fn ($group) => $group->pluck('ip_address')->sort()->implode(', '));

        $data = $rows->map(fn ($r) => [
            'Date' => $r->day,
            'User' => ($r->user_id ? $userNames[$r->user_id] ?? null : null) ?? $r->user_name ?? 'System',
            'Total Actions' => (int) $r->total,
            'Logins' => (int) $r->logins,
            'Failed Logins' => (int) $r->failed_logins,
            'Sales' => (int) $r->sales,
            'Void/Exchange Requests' => (int) $r->void_requests,
            'Void Approvals/Rejections' => (int) $r->void_decisions,
            'Receipt Prints' => (int) $r->prints,
            'Records Created' => (int) $r->created,
            'Records Updated' => (int) $r->updated,
            'Records Deleted' => (int) $r->deleted,
            'First Activity' => substr((string) $r->first_at, 11, 8),
            'Last Activity' => substr((string) $r->last_at, 11, 8),
            'IP Addresses' => $ips[$r->day.'|'.$r->user_id] ?? '',
        ]);

        $sum = fn (string $key) => $data->sum($key);

        $data->push([
            'Date' => 'TOTAL',
            'User' => '',
            'Total Actions' => $sum('Total Actions'),
            'Logins' => $sum('Logins'),
            'Failed Logins' => $sum('Failed Logins'),
            'Sales' => $sum('Sales'),
            'Void/Exchange Requests' => $sum('Void/Exchange Requests'),
            'Void Approvals/Rejections' => $sum('Void Approvals/Rejections'),
            'Receipt Prints' => $sum('Receipt Prints'),
            'Records Created' => $sum('Records Created'),
            'Records Updated' => $sum('Records Updated'),
            'Records Deleted' => $sum('Records Deleted'),
            'First Activity' => '',
            'Last Activity' => '',
            'IP Addresses' => '',
        ]);

        return $data;
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
            return ['Date', 'User', 'Total Actions', 'Logins', 'Failed Logins', 'Sales', 'Void/Exchange Requests', 'Void Approvals/Rejections', 'Receipt Prints', 'Records Created', 'Records Updated', 'Records Deleted', 'First Activity', 'Last Activity', 'IP Addresses'];
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
