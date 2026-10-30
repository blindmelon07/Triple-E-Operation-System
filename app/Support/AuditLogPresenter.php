<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

/**
 * Turns a raw audit log entry into something a person can read: a headline,
 * a coloured action label, and a field-by-field list of changes with
 * human labels, peso amounts, readable dates and IDs resolved to names.
 */
class AuditLogPresenter
{
    /** Bookkeeping columns that add noise without telling the reader anything. */
    protected const HIDDEN_FIELDS = ['created_at', 'updated_at', 'deleted_at', 'remember_token', 'password'];

    protected const MONEY_FIELDS = [
        'amount', 'total', 'subtotal', 'price', 'cost', 'balance', 'discount', 'discrepancy',
        'opening_amount', 'closing_amount', 'expected_amount', 'amount_paid', 'amount_paid_now',
        'down_payment', 'delivery_fee', 'withholding_amount', 'new_balance', 'total_sales',
        'total_cash_sales', 'payments_reversed', 'unit_price', 'net_pay', 'gross_pay',
    ];

    /** Foreign keys whose model can't be guessed from the column name, per auditable model. */
    protected const RELATION_OVERRIDES = [
        'Expense.category_id' => ExpenseCategory::class,
    ];

    /** Columns tried, in order, to give a related record a readable name. */
    protected const NAME_COLUMNS = ['name', 'title', 'reference_number', 'invoice_number', 'label'];

    public function __construct(protected AuditLog $log) {}

    public function actionLabel(): string
    {
        return match ($this->log->action) {
            'login' => 'Logged in',
            'logout' => 'Logged out',
            'login_failed' => 'Failed login',
            default => Str::ucfirst(str_replace('_', ' ', $this->log->action)),
        };
    }

    public static function actionColor(string $action): string
    {
        return match (true) {
            Str::contains($action, ['deleted', 'rejected', 'failed', 'void', 'cancelled', 'removed']) => 'danger',
            Str::contains($action, ['created', 'completed', 'opened', 'approved', 'added']) => 'success',
            Str::contains($action, ['updated', 'settled', 'closed', 'applied']) => 'info',
            Str::contains($action, ['requested', 'reverted']) => 'warning',
            $action === 'login' => 'primary',
            default => 'gray',
        };
    }

    public function modelLabel(): ?string
    {
        return $this->log->auditable_type ? Str::headline(class_basename($this->log->auditable_type)) : null;
    }

    /**
     * 'update' shows before → after; 'snapshot' shows a single value column
     * (records created, deleted, or custom actions that only log details).
     */
    public function mode(): string
    {
        return filled($this->log->old_values) && filled($this->log->new_values) ? 'update' : 'snapshot';
    }

    /**
     * @return array<int, array{field: string, old: ?array, new: ?array}>
     */
    public function changes(): array
    {
        $old = $this->log->old_values ?? [];
        $new = $this->log->new_values ?? [];

        // A deleted record's last state lives in old_values; show it as the snapshot.
        if ($this->mode() === 'snapshot' && $new === []) {
            [$old, $new] = [[], $old];
        }

        $fields = array_values(array_diff(array_unique([...array_keys($new), ...array_keys($old)]), self::HIDDEN_FIELDS));

        return array_map(fn (string $field) => [
            'field' => $this->fieldLabel($field),
            'old' => array_key_exists($field, $old) ? $this->value($field, $old[$field]) : null,
            'new' => array_key_exists($field, $new) ? $this->value($field, $new[$field]) : null,
        ], $fields);
    }

    protected function fieldLabel(string $field): string
    {
        if ($field === 'id') {
            return 'ID';
        }

        $label = Str::headline(Str::endsWith($field, '_id') ? Str::beforeLast($field, '_id') : $field);

        return preg_replace_callback('/\b(Soa|Or|Vat|Tin|Ip|Id|Sku)\b/', fn ($m) => strtoupper($m[1]), $label);
    }

    /**
     * @return array{text: string, hint: ?string, empty: bool, mono: bool}
     */
    protected function value(string $field, mixed $value): array
    {
        $make = fn (string $text, ?string $hint = null, bool $mono = false) => ['text' => $text, 'hint' => $hint, 'empty' => false, 'mono' => $mono];

        if ($value === null || $value === '') {
            return ['text' => 'Empty', 'hint' => null, 'empty' => true, 'mono' => false];
        }

        if (is_bool($value)) {
            return $make($value ? 'Yes' : 'No');
        }

        if (is_array($value)) {
            return $make(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), mono: true);
        }

        if (in_array($field, self::MONEY_FIELDS, true) && is_numeric($value)) {
            return $make('₱'.number_format((float) $value, 2));
        }

        if (Str::endsWith($field, '_id') && is_numeric($value) && ($name = $this->relatedName($field, $value))) {
            return $make($name, '#'.$value);
        }

        if (is_string($value) && ($date = $this->date($value))) {
            return $date;
        }

        if (in_array($field, ['status', 'payment_status', 'payment_method', 'type'], true) && is_string($value)) {
            return $make(Str::headline($value));
        }

        return $make((string) $value);
    }

    protected function date(string $value): ?array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?(\.\d+)?(Z|[+-]\d{2}:?\d{2})?)?$/', $value)) {
            return null;
        }

        try {
            // Values serialised from date casts are UTC ("…T16:00:00.000000Z"); show them in local time.
            $date = Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (Throwable) {
            return null;
        }

        $text = $date->format('H:i:s') === '00:00:00' ? $date->format('M j, Y') : $date->format('M j, Y g:i A');

        return ['text' => $text, 'hint' => null, 'empty' => false, 'mono' => false];
    }

    protected function relatedName(string $field, int|string $id): ?string
    {
        $owner = $this->log->auditable_type ? class_basename($this->log->auditable_type) : '';
        $class = self::RELATION_OVERRIDES["{$owner}.{$field}"]
            ?? 'App\\Models\\'.Str::studly(Str::beforeLast($field, '_id'));

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        try {
            $related = $class::query()->find($id);
        } catch (Throwable) {
            return null;
        }

        foreach (self::NAME_COLUMNS as $column) {
            if (filled($related?->getAttribute($column))) {
                return (string) $related->getAttribute($column);
            }
        }

        return null;
    }
}
