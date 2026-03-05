<?php

namespace App\Datatables\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Service for filtering, sorting, paginating in-memory datasets
 */
class CollectionDataService {
    /**
     * Filter a collection using table dropdown filters and form filters
     * - Dropdown filters use keys like "<column>_filter" with array values
     * - Form filters are provided via $filterFields describing types
     * - Supports 'text', 'select', 'date', 'range-date' basic semantics
     * - Supports filterColumnMapping with type 'array'
     */
    public function filterCollection(
        Collection $collection,
        array $filters,
        array $filterFields = [],
        array $filterColumnMapping = []
    ): Collection {
        // Column dropdown filters
        foreach ($filters as $key => $value) {
            if ($this->isColumnDropdownFilter($key, $value)) {
                $column        = substr($key, 0, -7); // remove '_filter'
                $allowedValues = $this->convertDisplayValuesToData($column, (array) $value, $filterColumnMapping);
                $collection    = $collection->filter(function ($row) use ($column, $allowedValues) {
                    $rowValue = $this->normalizeValue(data_get($row, $column));

                    return in_array($rowValue, array_map([$this, 'normalizeValue'], $allowedValues), true);
                })->values();
            }
        }

        // Form filters (basic support)
        foreach ($filterFields as $field => $config) {
            $type  = $config['type'] ?? 'text';
            $value = $filters[$field] ?? null;

            if ($this->isEmpty($value)) {
                continue;
            }

            switch ($type) {
                case 'select':
                case 'text':
                case 'date':
                    $collection = $collection->filter(function ($row) use ($field, $value) {
                        $rowValue    = $this->normalizeValue(data_get($row, $field));
                        $filterValue = $this->normalizeValue($value);
                        // For text, simple contains; for select/date, equality still works with normalize
                        if (is_string($value) && $value !== '' && !str_ends_with($field, '_id')) {
                            return str_contains(strtolower($rowValue), strtolower($filterValue));
                        }

                        return $rowValue === $filterValue;
                    })->values();
                    break;
                case 'range-date':
                    // Expect combined value 'from,to' (handled by trait)
                    [$from, $to] = $this->parseRangeDateCombined($value);
                    $collection  = $collection->filter(function ($row) use ($field, $from, $to) {
                        $rowValue = $this->normalizeValue(data_get($row, $field));
                        if ($from && $rowValue < $from) {
                            return false;
                        }
                        if ($to && $rowValue > $to) {
                            return false;
                        }

                        return true;
                    })->values();
                    break;
            }
        }

        return $collection;
    }

    /**
     * Sort a collection by key, normalizing null/bool to strings for consistency
     */
    public function sortCollection(Collection $collection, string $sortBy, string $direction): Collection {
        if ($sortBy === '') {
            return $collection;
        }

        $isDescending = strtolower($direction) === 'desc';

        return $collection->sortBy(
            fn($item) => $this->normalizeSortValue(data_get($item, $sortBy)),
            SORT_REGULAR,
            $isDescending
        )->values();
    }

    /**
     * Paginate an in-memory collection
     */
    public function paginateCollection(Collection $collection, int $perPage): LengthAwarePaginator {
        $currentPage = max(1, (int) Paginator::resolveCurrentPage('page'));
        $offset      = ($currentPage - 1) * $perPage;
        $items       = $collection->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * Compute distinct values per column for header filter panel
     */
    public function computeDistinctValues(Collection $collection, array $columns): array {
        $result = [];
        foreach ($columns as $column) {
            $result[$column] = $collection
                ->pluck($column)
                ->map(fn($v) => $this->normalizeValue($v))
                ->unique()
                ->sort()
                ->values()
                ->toArray();
        }

        return $result;
    }

    /**
     * Helpers
     */
    protected function isColumnDropdownFilter(string $key, $value): bool {
        return str_ends_with($key, '_filter') && is_array($value) && count($value) > 0;
    }

    protected function isEmpty(mixed $value): bool {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    protected function parseRangeDateCombined(string $value): array {
        $parts = explode(',', $value);
        $from  = isset($parts[0]) && $parts[0] !== 'null' && $parts[0] !== '' ? $parts[0] : null;
        $to    = isset($parts[1]) && $parts[1] !== 'null' && $parts[1] !== '' ? $parts[1] : null;

        return [$from, $to];
    }

    protected function normalizeValue($value): string {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    protected function normalizeSortValue($value): string {
        return $this->normalizeValue($value);
    }

    /**
     * Convert display values to original data values using mapping
     * Supports mapping type 'array' => ['values' => [key => label]]
     */
    protected function convertDisplayValuesToData(string $column, array $displayValues, array $filterColumnMapping): array {
        if (!isset($filterColumnMapping[$column]) || !is_array($filterColumnMapping[$column])) {
            return $displayValues;
        }

        $mapping = $filterColumnMapping[$column];
        if (($mapping['type'] ?? null) === 'array' && isset($mapping['values']) && is_array($mapping['values'])) {
            $flipped = array_flip($mapping['values']); // label => key
            $result  = [];
            foreach ($displayValues as $label) {
                $labelNorm = $this->normalizeValue($label);
                if ($labelNorm === '') {
                    $result[] = '';

                    continue;
                }
                if (isset($flipped[$label])) {
                    $result[] = $flipped[$label];
                }
            }

            return array_values(array_unique($result));
        }

        return $displayValues;
    }
}
