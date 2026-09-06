<?php

namespace App\Support\ReportBuilder;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Turns a ReportModules definition plus user-picked columns/filters into an
 * actual query and a flat array of rows — shared by the Custom Report Builder
 * page's on-screen preview and its CSV/PDF exports so the three can never
 * drift out of sync with each other.
 */
class ReportQueryService
{
    /** @var array<string, mixed> */
    private array $definition;

    public function __construct(private string $moduleKey)
    {
        $definition = ReportModules::get($moduleKey);

        if (! $definition) {
            throw new \InvalidArgumentException("Unknown report module: {$moduleKey}");
        }

        $this->definition = $definition;
    }

    /**
     * @param  array<string, mixed>  $filters  filter keys from the module's
     *   'filters' config, plus 'date_from' / 'date_to'.
     */
    public function buildQuery(array $filters = []): Builder
    {
        $model = $this->definition['model'];
        $query = $model::query();

        if (! empty($this->definition['eager'])) {
            $query->with($this->definition['eager']);
        }

        if (! empty($this->definition['date_column'])) {
            $this->applyDateRange($query, $this->definition['date_column'], $filters['date_from'] ?? null, $filters['date_to'] ?? null);
        }

        foreach (($this->definition['filters'] ?? []) as $key => $filterDef) {
            $value = $filters[$key] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $this->applyEquals($query, $filterDef['column'], $value);
        }

        return $query;
    }

    private function applyDateRange(Builder $query, string $column, ?string $from, ?string $to): void
    {
        if (! $from && ! $to) {
            return;
        }

        if (str_contains($column, '.')) {
            [$relation, $col] = $this->splitRelationColumn($column);
            $query->whereHas($relation, function (Builder $q) use ($col, $from, $to) {
                if ($from) $q->whereDate($col, '>=', $from);
                if ($to) $q->whereDate($col, '<=', $to);
            });

            return;
        }

        if ($from) $query->whereDate($column, '>=', $from);
        if ($to) $query->whereDate($column, '<=', $to);
    }

    private function applyEquals(Builder $query, string $column, mixed $value): void
    {
        if (str_contains($column, '.')) {
            [$relation, $col] = $this->splitRelationColumn($column);
            $query->whereHas($relation, fn (Builder $q) => $q->where($col, $value));

            return;
        }

        $query->where($column, $value);
    }

    /**
     * @return array{0: string, 1: string} [relation path, column]
     */
    private function splitRelationColumn(string $dotted): array
    {
        $parts = explode('.', $dotted);
        $column = array_pop($parts);

        return [implode('.', $parts), $column];
    }

    /**
     * @param  array<int, string>  $columns  whitelisted column keys from the module's config
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>> rows keyed by the same column keys, in the given order
     */
    public function rows(array $columns, array $filters = [], int $limit = 2000): Collection
    {
        $allowed = array_keys($this->definition['columns']);
        $columns = array_values(array_intersect($columns, $allowed));

        if (empty($columns)) {
            return collect();
        }

        $records = $this->buildQuery($filters)->limit($limit)->get();

        return $records->map(function ($record) use ($columns) {
            $row = [];
            foreach ($columns as $key) {
                $row[$key] = $this->formatValue(data_get($record, $key));
            }

            return $row;
        });
    }

    /**
     * The true number of matching records, ignoring rows()'s $limit — so the UI
     * can say "showing 2,000 of 2,543" instead of silently under-reporting.
     *
     * @param  array<string, mixed>  $filters
     */
    public function totalCount(array $filters = []): int
    {
        return $this->buildQuery($filters)->count();
    }

    private function formatValue(mixed $value): mixed
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i');
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    public function label(): string
    {
        return $this->definition['label'];
    }

    /**
     * @return array<string, string>
     */
    public function columnLabels(): array
    {
        return $this->definition['columns'];
    }
}
