<?php

namespace App\Datatables\Components;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

use App\Datatables\Services\CollectionDataService;

/**
 * Livewire DataTable component for Array/Collection data sources
 *
 * Reuses BaseDatatables views/JS and mirrors behavior of ModelDatatables
 * for filtering, sorting and pagination.
 */
class ArrayDatatables extends BaseDatatables {
    /**
     * Data source rows (array|Collection of associative arrays/objects)
     */
    public array|Collection $rows = [];

    /**
     * Optional mapping for header filter panel generation
     */
    public array $filterPanel = [];

    /**
     * Optional mapping used to convert display values to data values for filters
     * Supported types: ['array' => ['values' => [key => label, ...]]]
     */
    public array $filterColumnMapping = [];

    /**
     * Pre-generated column values for header filter panels
     */
    public array $columnFilterValues = [];

    /**
     * Current page number
     */
    public int $currentPage = 1;

    /**
     * Shim class name for view compatibility (static API for filter panel columns)
     */
    public string $modelClass = \App\Datatables\Models\VirtualArrayModel::class;

    /** @var CollectionDataService|null */
    protected ?CollectionDataService $collectionService = null;

    /**
     * Mount component with configuration and services
     */
    public function mount(array $config = []): void {
        parent::mount($config);
        $this->initializeCollectionService();

        // Expose filter panel columns to the shim used by the Blade view
        \App\Datatables\Models\VirtualArrayModel::setFilterPanel($this->filterPanel ?? []);

        // Pre-generate distinct values for header filter panels
        $this->generateColumnFilterValues();
    }

    /**
     * Initialize service
     */
    protected function initializeCollectionService(): void {
        if (!$this->collectionService) {
            $this->collectionService = app(CollectionDataService::class);
        }
    }

    /**
     * Default configuration merged with BaseDatatables defaults
     */
    public function getDefaultConfig(): array {
        $base = parent::getDefaultConfig();

        return array_merge($base, [
            'rows'                => [],
            'filterPanel'         => [],
            'filterColumnMapping' => [],
        ]);
    }

    /**
     * Override to expose computed column filter values to the view
     */
    protected function getColumnFilterValues(): array {
        return $this->columnFilterValues;
    }

    /**
     * Update data dynamically via AJAX
     *
     * @param  array  $newData
     * @return void
     */
    public function updateData(array $newData): void {
        if (isset($newData['rows'])) {
            $this->rows = $newData['rows'];
        }
        if (isset($newData['columns'])) {
            $this->columns = $newData['columns'];
        }
        if (isset($newData['filterPanel'])) {
            $this->filterPanel = $newData['filterPanel'];
        }
        if (isset($newData['filterColumnMapping'])) {
            $this->filterColumnMapping = $newData['filterColumnMapping'];
        }

        // Regenerate column filter values
        $this->generateColumnFilterValues();

        // Reset pagination to first page
        $this->currentPage = 1;

        // Re-render the component
        $this->render();
    }

    /**
     * Compute distinct values for header filter panels
     */
    protected function generateColumnFilterValues(): void {
        $this->initializeCollectionService();
        $dataset = $this->toCollection($this->rows);

        if (empty($this->filterPanel)) {
            $this->columnFilterValues = [];

            return;
        }

        $this->columnFilterValues = $this->collectionService->computeDistinctValues(
            $dataset,
            $this->filterPanel
        );
    }

    /**
     * Build paginated data from in-memory dataset
     */
    public function getDataProperty(): LengthAwarePaginator {
        $this->initializeCollectionService();

        $dataset = $this->toCollection($this->rows);

        // Apply table header dropdown filters ("<column>_filter") and form filters
        $filtered = $this->collectionService->filterCollection(
            $dataset,
            $this->filters,
            $this->filterFields,
            $this->filterColumnMapping
        );

        // Sort by column
        $sorted = $this->collectionService->sortCollection(
            $filtered,
            $this->sortBy,
            $this->sortDirection
        );

        // Paginate
        return $this->collectionService->paginateCollection($sorted, $this->perPage);
    }

    /**
     * Helper to convert mixed input to Collection
     */
    protected function toCollection(mixed $rows): Collection {
        if ($rows instanceof Collection) {
            return $rows->values();
        }
        if (is_array($rows)) {
            return collect($rows)->values();
        }

        return collect([]);
    }
}
