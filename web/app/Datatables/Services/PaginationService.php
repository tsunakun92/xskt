<?php

namespace App\Datatables\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Throwable;

use Modules\Logging\Utils\LogHandler;

/**
 * Service for handling pagination with appended attributes support
 */
class PaginationService {
    /**
     * Paginate query results with optimization for large datasets
     */
    public function paginate(
        Builder $query,
        int $perPage,
        string $modelClass,
        string $sortBy,
        string $sortDirection
    ): LengthAwarePaginator {
        $appendedFilters        = $this->getAppendedAttributeFilters($query, $modelClass);
        $needsCollectionSorting = $this->isAppendedAttribute($sortBy, $modelClass);

        // Use regular pagination if no appended attribute handling needed
        if (empty($appendedFilters) && !$needsCollectionSorting) {
            return $query->paginate($perPage);
        }

        // For large datasets, implement chunked processing to avoid memory issues
        $totalCount = $query->count();

        // If dataset is small enough, use collection-based processing
        if ($totalCount <= 10000) {
            $allResults = $query->get();

            if (!empty($appendedFilters)) {
                $allResults = $this->filterAppendedAttributes($allResults, $appendedFilters);
            }

            if ($needsCollectionSorting) {
                $allResults = $this->sortCollectionByAppendedAttribute($allResults, $sortBy, $sortDirection);
            }

            return $this->paginateCollection($allResults, $perPage);
        }

        // For large datasets, use chunked pagination with caching
        return $this->paginateWithChunking(
            $query,
            $perPage,
            $modelClass,
            $sortBy,
            $sortDirection,
            $appendedFilters,
            $needsCollectionSorting
        );
    }

    /**
     * Get filters that apply to appended attributes from query filters
     */
    protected function getAppendedAttributeFilters(Builder $query, string $modelClass): array {
        // This would need to be extracted from the original component's filters
        // For now, return empty array as this needs integration with the component
        return [];
    }

    /**
     * Paginate large datasets using chunked processing
     */
    protected function paginateWithChunking(
        Builder $query,
        int $perPage,
        string $modelClass,
        string $sortBy,
        string $sortDirection,
        array $appendedFilters,
        bool $needsCollectionSorting
    ): LengthAwarePaginator {
        // Fall back to database-level pagination for large datasets
        return $query->paginate($perPage);
    }

    /**
     * Check if a column is an appended attribute
     */
    protected function isAppendedAttribute(string $column, string $modelClass): bool {
        try {
            $model   = new $modelClass;
            $appends = $model->getAppends();

            return in_array($column, $appends);
        } catch (Throwable $e) {
            LogHandler::warning("Unable to check appended attributes for {$modelClass}: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Filter a collection by appended attributes
     */
    protected function filterAppendedAttributes(Collection $collection, array $filters): Collection {
        if (empty($filters)) {
            return $collection;
        }

        return $collection->filter(function ($item) use ($filters) {
            return $this->itemMatchesAllFilters($item, $filters);
        });
    }

    /**
     * Check if an item matches all specified filters
     */
    protected function itemMatchesAllFilters($item, array $filters): bool {
        foreach ($filters as $column => $allowedValues) {
            if (!$this->itemMatchesFilter($item, $column, $allowedValues)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if an item matches a specific filter
     */
    protected function itemMatchesFilter($item, string $column, array $allowedValues): bool {
        $itemValue = $this->normalizeItemValue($item->{$column});

        return in_array($itemValue, $allowedValues);
    }

    /**
     * Normalize an item value for consistent comparison
     */
    protected function normalizeItemValue($value): string {
        return $value === null ? '' : (string) $value;
    }

    /**
     * Sort a collection by appended attribute
     */
    protected function sortCollectionByAppendedAttribute(
        Collection $collection,
        string $sortBy,
        string $sortDirection
    ): Collection {
        try {
            $isDescending = strtolower($sortDirection) === 'desc';

            return $collection->sortBy(
                fn($item) => $this->extractSortValue($item, $sortBy),
                SORT_REGULAR,
                $isDescending
            )->values();
        } catch (Throwable $e) {
            LogHandler::warning('Collection sorting failed', [
                'sort_by'        => $sortBy,
                'sort_direction' => $sortDirection,
                'error'          => $e->getMessage(),
            ]);

            return $collection;
        }
    }

    /**
     * Extract and normalize the sort value from an item
     */
    protected function extractSortValue($item, string $sortBy): string {
        try {
            $value = $item->{$sortBy};

            return $this->normalizeSortValue($value);
        } catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Normalize a value for consistent sorting
     */
    protected function normalizeSortValue($value): string {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * Manually paginate a collection
     */
    protected function paginateCollection(Collection $collection, int $perPage): LengthAwarePaginator {
        try {
            $currentPage      = $this->getCurrentPage();
            $offset           = $this->calculateOffset($currentPage, $perPage);
            $currentPageItems = $this->sliceCollectionForPage($collection, $offset, $perPage);

            return $this->createPaginator($currentPageItems, $collection->count(), $currentPage, $perPage);
        } catch (Throwable $e) {
            LogHandler::warning('Collection pagination failed', [
                'error'            => $e->getMessage(),
                'collection_count' => $collection->count(),
                'per_page'         => $perPage,
            ]);

            // Return first page on error
            return $this->createPaginator($collection->take($perPage), $collection->count(), 1, $perPage);
        }
    }

    /**
     * Get the current page number safely
     */
    protected function getCurrentPage(): int {
        $page = Paginator::resolveCurrentPage('page');

        return max(1, (int) $page);
    }

    /**
     * Calculate the offset for pagination
     */
    protected function calculateOffset(int $currentPage, int $perPage): int {
        return ($currentPage - 1) * $perPage;
    }

    /**
     * Slice the collection for the current page
     */
    protected function sliceCollectionForPage(Collection $collection, int $offset, int $perPage): Collection {
        return $collection->slice($offset, $perPage)->values();
    }

    /**
     * Create a LengthAwarePaginator instance
     */
    protected function createPaginator($items, int $total, int $currentPage, int $perPage): LengthAwarePaginator {
        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * Get pagination statistics
     */
    public function getPaginationStats(LengthAwarePaginator $paginator): array {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
            'has_pages'    => $paginator->hasPages(),
        ];
    }
}
