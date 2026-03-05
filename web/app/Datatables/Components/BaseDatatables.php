<?php

namespace App\Datatables\Components;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

use App\Datatables\Constants\DatatableConstants;
use App\Datatables\Contracts\DatatableConfigInterface;
use App\Datatables\Services\ConfigurationService;
use App\Datatables\Services\ErrorHandlingService;
use App\Datatables\Services\FilterSessionService;
use App\Datatables\Traits\DatatableHelperTrait;
use App\Datatables\Traits\FilterFormTrait;
use App\Datatables\Traits\SortableTrait;

/**
 * Abstract base class for Livewire DataTable components
 *
 * Provides core functionality for data tables including:
 * - Configuration management
 * - Error handling
 * - Pagination
 * - Filtering and sorting
 * - Loading states
 */
abstract class BaseDatatables extends Component implements DatatableConfigInterface {
    use DatatableHelperTrait;
    use FilterFormTrait;
    use SortableTrait;
    use WithPagination;

    // Core configuration properties
    public array $columns         = [];

    public array $sortableColumns = [];

    public array $groupColumns    = [];

    public int $perPage;

    public string $emptyMessage;

    public string $routeName;

    public array $filterFields = [];

    public array $extraActions = [];

    /**
     * Optional custom filter form blade view name.
     * When set, this view will be used instead of the default filter form.
     *
     * @var string|null
     */
    public ?string $extraFilterForm = null;

    /**
     * Whether to show default view/edit/delete actions in the action column.
     * When set to false, only extraActions (if any) will be rendered.
     */
    public bool $defaultActions = true;

    public bool $showFilterPanel;

    public bool $showFilterForm;

    // Pagination configuration
    public int $paginationRange     = 1;

    public bool $showEllipsis       = true;

    public int $minPagesForEllipsis = 7;

    // State management
    public string $sortBy        = 'id';

    public string $sortDirection = 'desc';

    public array $filters        = [];

    public array $pendingFilters = [];

    public bool $hasError        = false;

    public ?string $errorMessage = null;

    // Session-based datatable state
    public string $state          = '';

    public array $collapsedGroups = [];

    /**
     * Persisted UI state for filter sections (e.g. open/closed).
     *
     * @var array<string, bool>
     */
    public array $filterSectionStates = [];

    // Services
    protected ?ConfigurationService $configService        = null;

    protected ?ErrorHandlingService $errorService         = null;

    protected ?FilterSessionService $filterSessionService = null;

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'state' => ['except' => '', 'as' => 'state'],
    ];

    /**
     * Mount component with dependency injection
     */
    public function mount(array $config = []): void {
        $this->initializeServices();
        $this->setup($config);
        $this->initializeFilterState();
        $this->initializeRangeDateFilters();
        $this->initializePendingFilters();
    }

    /**
     * Initialize required services
     */
    protected function initializeServices(): void {
        $this->configService        = app(ConfigurationService::class);
        $this->errorService         = app(ErrorHandlingService::class);
        $this->filterSessionService = app(FilterSessionService::class);
    }

    /**
     * Get configuration service with lazy initialization
     */
    protected function getConfigService(): ConfigurationService {
        if (!$this->configService) {
            $this->configService = app(ConfigurationService::class);
        }

        return $this->configService;
    }

    /**
     * Get error handling service with lazy initialization
     */
    protected function getErrorService(): ErrorHandlingService {
        if (!$this->errorService) {
            $this->errorService = app(ErrorHandlingService::class);
        }

        return $this->errorService;
    }

    /**
     * Get filter session service with lazy initialization
     */
    protected function getFilterSessionService(): FilterSessionService {
        if (!$this->filterSessionService) {
            $this->filterSessionService = app(FilterSessionService::class);
        }

        return $this->filterSessionService;
    }

    /**
     * Hydrate component state
     */
    public function hydrate(): void {
        $this->initializeRangeDateFilters();
    }

    /**
     * Initialize filter state from session or URL parameters
     */
    protected function initializeFilterState(): void {
        // If we have a state session key, load from session
        if (!empty($this->state) && $this->getFilterSessionService()->isValidSessionKey($this->state)) {
            $this->loadFilterStateFromSession($this->state);
        } else {
            // Check for legacy URL parameters for backward compatibility
            if ($this->hasLegacyUrlParameters()) {
                $this->migrateLegacyUrlParameters();
                // Only create session key if we migrated legacy parameters
                $this->createSessionKeyIfNeeded();
            }
        }
    }

    /**
     * Check if URL contains legacy parameters
     */
    protected function hasLegacyUrlParameters(): bool {
        $request = request();

        return $request->has('filters') ||
        $request->has('sortBy') ||
        $request->has('sortDirection') ||
        $request->has('perPage');
    }

    /**
     * Migrate legacy URL parameters to session storage
     */
    protected function migrateLegacyUrlParameters(): void {
        $request = request();

        // Load legacy parameters
        if ($request->has('filters')) {
            $this->filters = $request->get('filters', []);
        }

        if ($request->has('sortBy')) {
            $this->sortBy = $request->get('sortBy', 'id');
        }

        if ($request->has('sortDirection')) {
            $this->sortDirection = $request->get('sortDirection', 'asc');
        }

        if ($request->has('perPage')) {
            $this->perPage = (int) $request->get('perPage', 10);
        }
    }

    /**
     * Load filter state from session
     */
    protected function loadFilterStateFromSession(string $sessionKey): void {
        $filterState = $this->getFilterSessionService()->loadFilterState($sessionKey);

        $this->filters             = $filterState['filters'] ?? [];
        $this->sortBy              = $filterState['sortBy'] ?? 'id';
        $this->sortDirection       = $filterState['sortDirection'] ?? 'asc';
        $this->perPage             = $filterState['perPage'] ?? 10;
        $this->collapsedGroups     = $filterState['collapsedGroups'] ?? [];
        $this->filterSectionStates = $filterState['filterSectionStates'] ?? [];
    }

    /**
     * Create session key only when needed (when filters/sorts are applied)
     */
    protected function createSessionKeyIfNeeded(): void {
        if (empty($this->state) && $this->hasActiveFiltersOrSorts()) {
            $this->state = $this->getFilterSessionService()->generateSessionKey();
            $this->saveFilterStateToSession();
            $this->updateUrlWithStateKey();
        }
    }

    /**
     * Check if there are active filters or non-default sorts
     */
    protected function hasActiveFiltersOrSorts(): bool {
        // Check if there are any active filters
        $hasActiveFilters = !empty(array_filter($this->filters, function ($value) {
            return !$this->isEmptyFilterValue($value);
        }));

        // Check if sort is different from default
        $hasNonDefaultSort = $this->sortBy !== 'id' || $this->sortDirection !== 'desc';

        // Check if perPage is different from default
        $hasNonDefaultPerPage = $this->perPage !== 10;

        // Check if there are collapsed groups state
        $hasCollapsedGroups = !empty($this->collapsedGroups);

        // Check if there is non-default filter section state
        $hasFilterSectionState = !empty($this->filterSectionStates);

        return $hasActiveFilters || $hasNonDefaultSort || $hasNonDefaultPerPage || $hasCollapsedGroups || $hasFilterSectionState;
    }

    /**
     * Save current datatable state to session
     */
    protected function saveFilterStateToSession(): void {
        if (empty($this->state)) {
            $this->state = $this->getFilterSessionService()->generateSessionKey();
            // Update URL when generating new session key
            $this->updateUrlWithStateKey();
        }

        $filterData = [
            'filters'             => $this->filters,
            'sortBy'              => $this->sortBy,
            'sortDirection'       => $this->sortDirection,
            'perPage'             => $this->perPage,
            'collapsedGroups'     => $this->collapsedGroups,
            'filterSectionStates' => $this->filterSectionStates,
        ];

        $this->getFilterSessionService()->saveFilterState($this->state, $filterData);
    }

    /**
     * Update URL to include the state session key
     */
    protected function updateUrlWithStateKey(): void {
        if (empty($this->state)) {
            return;
        }

        // Use JavaScript to update the URL without page reload
        $this->dispatch('updateUrl', ['state' => $this->state, 'meta' => ['skipReinit' => true]]);
    }

    /**
     * Clear session key and update URL
     */
    protected function clearSessionKey(): void {
        if (!empty($this->state)) {
            $this->getFilterSessionService()->deleteFilterState($this->state);
            $this->state = '';
            // Update URL to remove state parameter
            $this->dispatch('updateUrl', ['state' => '']);
        }
    }

    /**
     * Persist collapsible group state (true => collapsed)
     */
    public function setGroupCollapsed(string $groupName, bool $isCollapsed): void {
        $this->collapsedGroups[$groupName] = $isCollapsed;
        // Ensure we have a session key if state deviates from default
        $this->createSessionKeyIfNeeded();
        $this->saveFilterStateToSession();
    }

    /**
     * Persist filter section state (true => open, false => closed).
     *
     * @param  string  $section
     * @param  bool  $isOpen
     * @return void
     */
    public function setFilterSectionState(string $section, bool $isOpen): void {
        $this->filterSectionStates[$section] = $isOpen;
        $this->createSessionKeyIfNeeded();
        $this->saveFilterStateToSession();
    }

    /**
     * Setup component configuration using service
     */
    protected function setup(array $config = []): void {
        $resolvedConfig = $this->getConfigService()->resolveConfiguration($config, $this->getDefaultConfig());
        $this->applyConfiguration($resolvedConfig);
    }

    /**
     * Get default configuration for this component
     */
    public function getDefaultConfig(): array {
        return [
            'perPage'             => DatatableConstants::getDefaultPageSize(),
            'emptyMessage'        => DatatableConstants::getEmptyMessage(),
            'routeName'           => '',
            'extraFilterForm'     => null,
            'defaultActions'      => true,
            'filterFields'        => [],
            'extraActions'        => [],
            'showFilterPanel'     => false,
            'showFilterForm'      => false,
            'columns'             => [],
            'sortableColumns'     => [],
            'groupColumns'        => [],
            'paginationRange'     => 1,
            'showEllipsis'        => true,
            'minPagesForEllipsis' => 7,
        ];
    }

    /**
     * Apply resolved configuration to component properties
     */
    public function applyConfiguration(array $config): void {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function updatedPerPage(): void {
        $this->withLoadingAndReset(function () {
            // Page reset handled by withLoadingAndReset
            $this->createSessionKeyIfNeeded();
            $this->saveFilterStateToSession();
        });
    }

    /**
     * Set loading state (disabled for better filter UX)
     */
    public function setLoading(bool $loading = true): void {
        // Loading state disabled to prevent UI flicker during filter interactions
        // $this->isLoading = $loading;
    }

    /**
     * Livewire hooks for loading state (disabled for better filter UX)
     */
    public function updating($property, $value): void {
        // Loading hooks disabled to prevent UI flicker during filter interactions
        // if (in_array($property, ['page', 'perPage'])) {
        //     $this->setLoading(true);
        // }
    }

    public function updated($property, $value): void {
        // Loading hooks disabled to prevent UI flicker during filter interactions
        // if (in_array($property, ['page', 'perPage'])) {
        //     $this->setLoading(false);
        // }
    }

    /**
     * Apply column filter
     */
    public function applyColumnFilter(string $column, array $values): void {
        $previousFilters = $this->filters;
        $this->withConditionalReset(function () use ($column, $values) {
            $this->filters[$column . '_filter'] = $values;
            $this->createSessionKeyIfNeeded();
            $this->saveFilterStateToSession();
        }, $previousFilters);
    }

    /**
     * Get column filter values (computed property)
     */
    public function getColumnFilterValuesProperty(): array {
        return $this->getColumnFilterValues();
    }

    /**
     * Get column filter values - implemented by child classes
     */
    protected function getColumnFilterValues(): array {
        return [];
    }

    /**
     * Get filter panel columns
     */
    public function getFilterPanelColumns(): array {
        return [];
    }

    /**
     * Get pagination structure with ellipsis support
     */
    public function getPaginationStructure(): array {
        if (!$this->getDataProperty()->hasPages()) {
            return [];
        }

        $current = $this->getDataProperty()->currentPage();
        $last    = $this->getDataProperty()->lastPage();

        if (!$this->showEllipsis || $last <= $this->minPagesForEllipsis) {
            // Simple pagination for few pages
            return $this->buildSimplePagination($current, $last);
        }

        return $this->buildAdvancedPagination($current, $last, $this->paginationRange);
    }

    /**
     * Build simple pagination structure without ellipsis
     */
    protected function buildSimplePagination(int $current, int $last): array {
        $pages = [];
        for ($i = 1; $i <= $last; $i++) {
            $pages[] = [
                'type'   => 'page',
                'number' => $i,
                'active' => $i === $current,
            ];
        }

        return $pages;
    }

    /**
     * Build advanced pagination structure with ellipsis
     */
    protected function buildAdvancedPagination(int $current, int $last, int $range): array {
        $pages = [];

        // Always show first page
        $pages[] = [
            'type'   => 'page',
            'number' => 1,
            'active' => $current === 1,
        ];

        $startRange = max(2, $current - $range);
        $endRange   = min($last - 1, $current + $range);

        // Add left ellipsis if needed
        if ($startRange > 2) {
            $pages[] = ['type' => 'ellipsis', 'label' => '...'];
        }

        // Add pages around current
        for ($i = $startRange; $i <= $endRange; $i++) {
            if ($i !== 1 && $i !== $last) {
                // Skip first and last (already handled)
                $pages[] = [
                    'type'   => 'page',
                    'number' => $i,
                    'active' => $i === $current,
                ];
            }
        }

        // Add right ellipsis if needed
        if ($endRange < $last - 1) {
            $pages[] = ['type' => 'ellipsis', 'label' => '...'];
        }

        // Always show last page (if not the same as first)
        if ($last > 1) {
            $pages[] = [
                'type'   => 'page',
                'number' => $last,
                'active' => $current === $last,
            ];
        }

        return $pages;
    }

    /**
     * Render component with centralized error handling
     */
    public function render() {
        return $this->getErrorService()->handleRender(
            fn() => $this->renderView(),
            $this->getRenderContext()
        );
    }

    /**
     * Render the main view
     */
    protected function renderView() {
        return view('datatables::components.datatables.index', [
            'columnFilterValues' => $this->getColumnFilterValues(),
            'hasErrors'          => $this->hasError,
            'errorMessage'       => $this->errorMessage,
        ]);
    }

    /**
     * Get context for error handling
     */
    protected function getRenderContext(): array {
        return [
            'component'     => static::class,
            'filters'       => $this->filters,
            'sortBy'        => $this->sortBy,
            'sortDirection' => $this->sortDirection,
        ];
    }

    /**
     * Get paginated data - implemented by child classes
     */
    abstract public function getDataProperty(): LengthAwarePaginator;

    /**
     * Set error state
     */
    public function setError(string $message, ?Throwable $exception = null): void {
        $this->hasError     = true;
        $this->errorMessage = $message;

        if ($exception) {
            $this->getErrorService()->logError($exception, $this->getRenderContext());
        }
    }

    /**
     * Clear error state
     */
    public function clearError(): void {
        $this->hasError     = false;
        $this->errorMessage = null;
    }

    /**
     * Initialize pending filters from current filters
     */
    protected function initializePendingFilters(): void {
        $this->pendingFilters = $this->filters;
    }

    /**
     * Apply pending filters to active filters and refresh data
     */
    public function applyFilters(): void {
        $previousFilters = $this->filters;
        $this->withConditionalReset(function () {
            $this->filters = $this->pendingFilters;
            $this->initializeRangeDateFilters();
            $this->createSessionKeyIfNeeded();
            $this->saveFilterStateToSession();
        }, $previousFilters);
    }

    /**
     * Clear all pending filters
     */
    public function clearPendingFilters(): void {
        $this->pendingFilters = [];

        // Clear range date sub-fields in pending filters
        foreach ($this->filterFields as $fieldName => $config) {
            if (($config['type'] ?? null) === 'range-date') {
                $this->pendingFilters[$fieldName . '_from'] = '';
                $this->pendingFilters[$fieldName . '_to']   = '';
            }
        }
    }

    /**
     * Get custom column renderers - implemented by child classes
     *
     * @return array
     */
    public function getCustomColumnRenderers(): array {
        return [];
    }

    /**
     * Get additional data needed for custom column rendering - implemented by child classes
     *
     * @return array
     */
    public function getCustomColumnData(): array {
        return [];
    }

    /**
     * Check if column has custom renderer
     *
     * @param  string  $column
     * @return bool
     */
    public function hasCustomRenderer(string $column): bool {
        $renderers = $this->getCustomColumnRenderers();

        return isset($renderers[$column]);
    }

    /**
     * Get custom renderer for column
     *
     * @param  string  $column
     * @return string|null
     */
    public function getCustomRenderer(string $column): ?string {
        $renderers = $this->getCustomColumnRenderers();

        return $renderers[$column] ?? null;
    }

    /**
     * Apply a quick date range preset to a range-date filter field and refresh data.
     *
     * @param  string  $field  Base field name (e.g. 'start', 'end')
     * @param  string  $preset  Preset key: 'yesterday', 'today', 'tomorrow', 'last_week', 'this_week', 'last_month', 'this_month'
     * @return void
     */
    public function applyQuickDateRange(string $field, string $preset): void {
        $today      = Carbon::today();
        $from       = null;
        $to         = null;

        switch ($preset) {
            case 'yesterday':
                $from = $today->copy()->subDay()->toDateString();
                $to   = $from;
                break;
            case 'today':
                $from = $today->toDateString();
                $to   = $from;
                break;
            case 'tomorrow':
                $from = $today->copy()->addDay()->toDateString();
                $to   = $from;
                break;
            case 'last_week':
                $from = $today->copy()->subWeek()->startOfWeek()->toDateString();
                $to   = $today->copy()->subWeek()->endOfWeek()->toDateString();
                break;
            case 'this_week':
                $from = $today->copy()->startOfWeek()->toDateString();
                $to   = $today->copy()->endOfWeek()->toDateString();
                break;
            case 'last_month':
                $from = $today->copy()->subMonth()->startOfMonth()->toDateString();
                $to   = $today->copy()->subMonth()->endOfMonth()->toDateString();
                break;
            case 'this_month':
                $from = $today->copy()->startOfMonth()->toDateString();
                $to   = $today->copy()->endOfMonth()->toDateString();
                break;
            default:
                return;
        }

        $this->pendingFilters[$field . '_from'] = $from;
        $this->pendingFilters[$field . '_to']   = $to;

        // Combine into main range field
        $this->updatePendingRangeDate($field);

        // Ensure section is marked as open and persist state
        $this->setFilterSectionState($field, true);

        // Apply filters immediately
        $this->applyFilters();
    }
}
