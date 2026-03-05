<?php

namespace App\Datatables\Components;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

use App\Datatables\Services\PaginationService;
use App\Datatables\Services\QueryBuilderService;
use Modules\Logging\Utils\LogHandler;

/**
 * Livewire DataTable component for Eloquent Models
 *
 * Extends BaseDatatables with model-specific functionality:
 * - Model configuration resolution
 * - Query building and filtering
 * - Column value generation
 * - Appended attribute handling
 */
class ModelDatatables extends BaseDatatables {
    public string $modelClass;

    public string $routeName;

    public array $columnFilterValues = [];

    // Injected services
    protected ?QueryBuilderService $queryService    = null;

    protected ?PaginationService $paginationService = null;

    /**
     * Mount component with configuration and services
     */
    public function mount(array $config = []): void {
        $this->validateModelClass($config['modelClass'] ?? null);
        $this->modelClass = $config['modelClass'];
        $this->routeName  = $config['routeName'] ?? '';

        parent::mount($config);
        $this->generateColumnFilterValues();
    }

    /**
     * Initialize services specific to ModelDatatables
     */
    protected function initializeServices(): void {
        parent::initializeServices();
        $this->queryService      = app(QueryBuilderService::class);
        $this->paginationService = app(PaginationService::class);
    }

    /**
     * Get query builder service with lazy initialization
     */
    protected function getQueryService(): QueryBuilderService {
        if (!$this->queryService) {
            $this->queryService = app(QueryBuilderService::class);
        }

        return $this->queryService;
    }

    /**
     * Get pagination service with lazy initialization
     */
    protected function getPaginationService(): PaginationService {
        if (!$this->paginationService) {
            $this->paginationService = app(PaginationService::class);
        }

        return $this->paginationService;
    }

    /**
     * Validate model class exists and has required methods
     */
    protected function validateModelClass(?string $modelClass): void {
        if (!$modelClass) {
            throw new InvalidArgumentException('modelClass is required for ModelDatatables');
        }

        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} does not exist");
        }

        if (!method_exists($modelClass, 'getAsDatatables')) {
            throw new InvalidArgumentException("Model {$modelClass} must implement getAsDatatables method");
        }
    }

    /**
     * Get default configuration merged with model-specific settings
     */
    public function getDefaultConfig(): array {
        $baseConfig = parent::getDefaultConfig();

        if (!$this->modelClass) {
            return $baseConfig;
        }

        $columns = [];
        if (method_exists($this->modelClass, 'getDatatableTableColumns')) {
            $columns = $this->modelClass::getDatatableTableColumns();
        } else {
            $columns = $this->modelClass::getBaseDatatableTableColumns();
        }

        return array_merge($baseConfig, [
            'columns'         => $columns,
            'sortableColumns' => $this->resolveSortableColumns($this->modelClass),
            'groupColumns'    => $this->resolveGroupColumns($this->modelClass),
            'filterFields'    => $this->resolveFilterFields($this->modelClass),
            'showFilterPanel' => method_exists($this->modelClass, 'showFilterPanel') ? $this->modelClass::showFilterPanel() : true,
            'showFilterForm'  => method_exists($this->modelClass, 'showFilterForm') ? $this->modelClass::showFilterForm() : true,
            'routeName'       => $this->routeName,
        ]);
    }

    /**
     * Resolve sortable columns from model
     */
    protected function resolveSortableColumns(string $modelClass): array {
        if (method_exists($modelClass, 'getSortableArray')) {
            return $modelClass::getSortableArray();
        }

        if (method_exists($modelClass, 'getDatatableTableColumns')) {
            return array_keys($modelClass::getDatatableTableColumns(false));
        }

        return array_keys($modelClass::getBaseDatatableTableColumns(false));
    }

    /**
     * Resolve group columns from model
     */
    protected function resolveGroupColumns(string $modelClass): array {
        return method_exists($modelClass, 'getDatatableTableGroupColumns')
        ? $modelClass::getDatatableTableGroupColumns()
        : [];
    }

    /**
     * Resolve filter fields from model
     */
    protected function resolveFilterFields(string $modelClass): array {
        return method_exists($modelClass, 'getFilterFields')
        ? $modelClass::getFilterFields($this->routeName)
        : [];
    }

    /**
     * Get paginated data using error handling service
     */
    public function getDataProperty(): LengthAwarePaginator {
        return $this->getErrorService()->handleDataFetch(
            fn() => $this->fetchPaginatedData(),
            $this->getDataContext()
        );
    }

    /**
     * Get context for data fetching
     */
    protected function getDataContext(): array {
        return [
            'component'     => static::class,
            'modelClass'    => $this->modelClass,
            'filters'       => $this->filters,
            'sortBy'        => $this->sortBy,
            'sortDirection' => $this->sortDirection,
            'perPage'       => $this->perPage,
        ];
    }

    /**
     * Fetch paginated data using query builder service with performance optimizations
     */
    protected function fetchPaginatedData(): LengthAwarePaginator {
        $query = $this->getQueryService()->buildQuery(
            $this->modelClass,
            $this->filters,
            $this->sortBy,
            $this->sortDirection
        );

        $query = $this->getQueryService()->applyColumnFilters($query, $this->filters, $this->modelClass);

        return $this->getPaginationService()->paginate(
            $query,
            $this->perPage,
            $this->modelClass,
            $this->sortBy,
            $this->sortDirection
        );
    }

    /**
     * Apply column filters to query
     */
    public function applyColumnFilters(Builder $query): Builder {
        $filterColumnMapping = $this->modelClass::getFilterColumnMapping();

        foreach ($this->filters as $filterKey => $filterValues) {
            if (!$this->isColumnFilter($filterKey, $filterValues)) {
                continue;
            }

            $column = str_replace('_filter', '', $filterKey);

            if ($this->shouldSkipColumn($column, $filterColumnMapping)) {
                continue;
            }

            $this->applySingleColumnFilter($query, $column, $filterValues, $filterColumnMapping);
        }

        return $query;
    }

    /**
     * Check if filter key represents column filter with values
     */
    protected function isColumnFilter(string $filterKey, $filterValues): bool {
        return str_ends_with($filterKey, '_filter') &&
        !empty($filterValues) &&
        is_array($filterValues) &&
        count($filterValues) > 0;
    }

    /**
     * Check if column should be skipped during filtering
     */
    protected function shouldSkipColumn(string $column, array $filterColumnMapping): bool {
        return $this->isAppendedAttribute($column) && !isset($filterColumnMapping[$column]);
    }

    /**
     * Apply single column filter to query
     */
    protected function applySingleColumnFilter(Builder $query, string $column, array $filterValues, array $filterColumnMapping): void {
        $databaseColumn       = $this->resolveDatabaseColumn($column, $filterColumnMapping);
        $databaseFilterValues = $this->convertFilterValues($column, $filterValues, $filterColumnMapping);
        $this->addWhereClause($query, $databaseColumn, $databaseFilterValues);
    }

    /**
     * Resolve database column name from mapping
     */
    protected function resolveDatabaseColumn(string $column, array $filterColumnMapping): string {
        if (!isset($filterColumnMapping[$column])) {
            return $column;
        }

        $mappingConfig = $filterColumnMapping[$column];

        if (is_array($mappingConfig)) {
            return $this->extractDatabaseColumnFromMapping($mappingConfig, $column);
        }

        return $mappingConfig;
    }

    /**
     * Extract database column from mapping config
     */
    protected function extractDatabaseColumnFromMapping(array $mappingConfig, string $fallbackColumn): string {
        if (isset($mappingConfig['type']) && in_array($mappingConfig['type'], ['relationship', 'array'])) {
            return $mappingConfig['column'] ?? $fallbackColumn;
        }

        return $fallbackColumn;
    }

    /**
     * Convert display filter values to database values
     */
    protected function convertFilterValues(string $column, array $filterValues, array $filterColumnMapping): array {
        if (isset($filterColumnMapping[$column])) {
            return $this->modelClass::convertDisplayValuesToDatabase($column, $filterValues);
        }

        return $filterValues;
    }

    /**
     * Add WHERE clause for column filtering
     */
    protected function addWhereClause(Builder $query, string $databaseColumn, array $databaseFilterValues): void {
        $query->where(function ($q) use ($databaseColumn, $databaseFilterValues) {
            if (in_array('', $databaseFilterValues)) {
                $q->whereIn($databaseColumn, $databaseFilterValues)
                    ->orWhereNull($databaseColumn)
                    ->orWhere($databaseColumn, '');
            } else {
                $q->whereIn($databaseColumn, $databaseFilterValues);
            }
        });
    }

    /**
     * Pre-generate filter values for filter panel columns
     */
    protected function generateColumnFilterValues(): void {
        $filterPanelColumns = $this->modelClass::getFilterPanelArray();

        foreach ($filterPanelColumns as $column) {
            try {
                $this->columnFilterValues[$column] = $this->modelClass::getFilterPanelColumnValues($column);
            } catch (Exception $e) {
                $this->columnFilterValues[$column] = [];
            }
        }
    }

    /**
     * Get pre-generated filter values for column
     */
    public function getColumnValues(string $column): array {
        return $this->columnFilterValues[$column] ?? [];
    }

    /**
     * Get column filter values for rendering
     * Override from BaseDatatables to return pre-generated values
     */
    protected function getColumnFilterValues(): array {
        return $this->columnFilterValues;
    }

    /**
     * Get custom column renderers from model
     *
     * @return array
     */
    public function getCustomColumnRenderers(): array {
        if (method_exists($this->modelClass, 'getCustomColumnRenderers')) {
            return $this->modelClass::getCustomColumnRenderers();
        }

        return parent::getCustomColumnRenderers();
    }

    /**
     * Get additional data needed for custom column rendering
     *
     * @return array
     */
    public function getCustomColumnData(): array {
        if (method_exists($this->modelClass, 'getCustomColumnData')) {
            return $this->modelClass::getCustomColumnData();
        }

        return parent::getCustomColumnData();
    }

    /**
     * Check if a column is an appended attribute.
     */
    protected function isAppendedAttribute(string $column): bool {
        /** @var \App\Models\BaseModel $model */
        $model = new $this->modelClass;

        // Get the model's appends array
        $appends = $model->getAppends();

        return in_array($column, $appends);
    }

    /**
     * Get filters that apply to appended attributes.
     */
    protected function getAppendedAttributeFilters(): array {
        $appendedFilters = [];

        foreach ($this->filters as $filterKey => $filterValues) {
            if (str_ends_with($filterKey, '_filter') && !empty($filterValues)) {
                $column = str_replace('_filter', '', $filterKey);

                if ($this->isAppendedAttribute($column)) {
                    $appendedFilters[$column] = $filterValues;
                }
            }
        }

        return $appendedFilters;
    }

    /**
     * Filter a collection by appended attributes.
     */
    protected function filterAppendedAttributes($collection, array $filters) {
        if (empty($filters)) {
            return $collection;
        }

        return $collection->filter(function ($item) use ($filters) {
            return $this->itemMatchesAllFilters($item, $filters);
        });
    }

    /**
     * Checks if an item matches all specified filters.
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
     * Checks if an item matches a specific filter.
     */
    protected function itemMatchesFilter($item, string $column, array $allowedValues): bool {
        $itemValue = $this->normalizeItemValue($item->{$column});

        return in_array($itemValue, $allowedValues);
    }

    /**
     * Normalizes an item value for consistent comparison.
     */
    protected function normalizeItemValue($value): string {
        return $value === null ? '' : (string) $value;
    }

    /**
     * Manually paginate a collection with enhanced error handling.
     */
    protected function paginateCollection($collection): LengthAwarePaginator {
        try {
            $currentPage      = $this->getCurrentPage();
            $offset           = $this->calculateOffset($currentPage);
            $currentPageItems = $this->sliceCollectionForPage($collection, $offset);

            return $this->createPaginator($currentPageItems, $collection->count(), $currentPage);
        } catch (Exception $e) {
            LogHandler::warning('Collection pagination failed', [
                'error'            => $e->getMessage(),
                'collection_count' => $collection->count(),
                'per_page'         => $this->perPage,
            ]);

            // Return first page on error
            return $this->createPaginator($collection->take($this->perPage), $collection->count(), 1);
        }
    }

    /**
     * Gets the current page number safely.
     */
    public function getCurrentPage(): int {
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage('page');

        return max(1, (int) $page);
    }

    /**
     * Calculates the offset for pagination.
     */
    protected function calculateOffset(int $currentPage): int {
        return ($currentPage - 1) * $this->perPage;
    }

    /**
     * Slices the collection for the current page.
     */
    protected function sliceCollectionForPage($collection, int $offset) {
        return $collection->slice($offset, $this->perPage)->values();
    }

    /**
     * Creates a LengthAwarePaginator instance.
     */
    protected function createPaginator($items, int $total, int $currentPage): LengthAwarePaginator {
        return new LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );
    }

    /**
     * Sort a collection by appended attribute with enhanced error handling.
     */
    protected function sortCollectionByAppendedAttribute($collection, string $sortBy, string $sortDirection) {
        try {
            $isDescending = $this->isDescendingSort($sortDirection);

            return $collection->sortBy(
                fn($item) => $this->extractSortValue($item, $sortBy),
                SORT_REGULAR,
                $isDescending
            )->values();
        } catch (Exception $e) {
            LogHandler::warning('Collection sorting failed', [
                'sort_by'        => $sortBy,
                'sort_direction' => $sortDirection,
                'error'          => $e->getMessage(),
            ]);

            // Return unsorted collection on error
            return $collection;
        }
    }

    /**
     * Determines if sorting should be in descending order.
     */
    protected function isDescendingSort(string $sortDirection): bool {
        return strtolower($sortDirection) === 'desc';
    }

    /**
     * Extracts and normalizes the sort value from an item.
     */
    protected function extractSortValue($item, string $sortBy): string {
        try {
            $value = $item->{$sortBy};

            return $this->normalizeSortValue($value);
        } catch (Exception $e) {
            // Return empty string for items that can't be accessed
            return '';
        }
    }

    /**
     * Normalizes a value for consistent sorting.
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
     * Apply filtering for relationship columns.
     */
    protected function applyRelationshipColumnFilter(Builder $query, string $column, array $filterValues): Builder {
        // This is a placeholder - implement based on your specific relationship column logic
        // For now, treat it as a regular column
        return $query->whereIn($column, $filterValues);
    }

    /**
     * Validates if a column is valid for filtering.
     */
    protected function isValidFilterColumn(string $column, array $filterColumnMapping): bool {
        // Check if column is in filter panel array
        if (method_exists($this->modelClass, 'getFilterPanelArray')) {
            $filterPanelColumns = $this->modelClass::getFilterPanelArray();
            if (!in_array($column, $filterPanelColumns)) {
                return false;
            }
        }

        // If there's no mapping, assume valid
        if (empty($filterColumnMapping)) {
            return true;
        }

        return isset($filterColumnMapping[$column]) ||
        in_array($column, (new $this->modelClass)->getFillable());
    }
}
