<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Gives any model search, sorting and (faceted/date) filtering in a declarative
 * way: the model only declares WHICH columns are allowed, not HOW they are filtered.
 *
 * Each model overrides these methods to declare its columns:
 *
 *   protected function searchable(): array { return ['name', 'address']; }
 *   protected function sortable(): array   { return ['name', 'created_at']; }
 *
 * And gets the withFilters() and withSorting() scopes for free.
 */
trait Filterable
{
    /**
     * Columns the search term is matched against.
     * The model overrides this with its own columns.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [];
    }

    /**
     * Columns that are allowed to be sorted by.
     * The model overrides this with its own columns.
     *
     * @return array<int, string>
     */
    protected function sortable(): array
    {
        return [];
    }

    /**
     * Bounded-value columns filtered by selection (faceted).
     * The model overrides this (e.g. ['status', 'type']).
     *
     * @return array<int, string>
     */
    protected function facetable(): array
    {
        return [];
    }

    /**
     * Default ordering when the user picks none.
     * Returns [column, direction]. The model overrides it
     * (e.g. ['created_at', 'desc'] to show newest first).
     *
     * @return array{0: string, 1: string}
     */
    protected function defaultSort(): array
    {
        return [$this->getKeyName(), 'asc'];
    }

    /**
     * Date columns filtered by range (from/to).
     * The model overrides this (e.g. ['created_at']).
     *
     * @return array<int, string>
     */
    protected function dateFilterable(): array
    {
        return [];
    }

    /**
     * Apply the validated filters (search and, optionally, faceted/date filters).
     *
     * @param  Builder<static>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<static>
     */
    public function scopeWithFilters(Builder $query, array $filters): Builder
    {
        $searchable = $this->searchable();

        // Free-text search: match the term across ALL declared columns.
        if (! empty($filters['search']) && ! empty($searchable)) {
            // Escape LIKE wildcards (% and _) so they are matched literally
            // and do not alter the pattern.
            $term = addcslashes((string) $filters['search'], '%_\\');

            $query->where(function (Builder $query) use ($term, $searchable): void {
                foreach ($searchable as $column) {
                    $query->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        // Faceted filters: for each declared facetable column, if a value
        // (or several) arrives in the filters, apply a whereIn.
        foreach ($this->facetable() as $column) {
            if (! empty($filters[$column])) {
                $values = (array) $filters[$column];
                $query->whereIn($column, $values);
            }
        }

        // Date range filters: for each declared column, accept
        // "{column}_from" and "{column}_to" (e.g. created_at_from / created_at_to).
        foreach ($this->dateFilterable() as $column) {
            if (! empty($filters[$column.'_from'])) {
                $query->whereDate($column, '>=', $filters[$column.'_from']);
            }

            if (! empty($filters[$column.'_to'])) {
                $query->whereDate($column, '<=', $filters[$column.'_to']);
            }
        }

        return $query;
    }

    /**
     * Order by an allowed column. If the field is not in sortable(), it falls
     * back to a safe default (prevents ordering by arbitrary columns).
     *
     * The primary key is always appended as the final tie-breaker: this makes
     * the ordering deterministic and keeps pagination from duplicating or
     * dropping rows, even when the sort column has repeated values (e.g. seeders
     * with the same created_at, or a status with few values).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithSorting(Builder $query, ?string $sort, string $direction = 'asc'): Builder
    {
        $key = $this->getKeyName();

        // If no valid sort is requested, use the model's default ordering.
        if (! $sort || ! in_array($sort, $this->sortable(), true)) {
            [$sort, $direction] = $this->defaultSort();
        }

        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sort, $direction);

        // Deterministic tie-breaker: only if we are not already ordering by the key.
        if ($sort !== $key) {
            $query->orderBy($key, $direction);
        }

        return $query;
    }
}
