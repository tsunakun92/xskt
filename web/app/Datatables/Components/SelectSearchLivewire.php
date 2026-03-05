<?php

namespace App\Datatables\Components;

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

use App\Datatables\Constants\DatatableConstants;
use App\Datatables\Services\CacheService;
use App\Datatables\Services\OptionLoaderService;

/**
 * Livewire select-search component with dependent dropdown support
 *
 * Features:
 * - Dynamic option loading from models
 * - Cascading dependencies
 * - Caching for performance
 * - Error handling and recovery
 * - Loading state management
 */
class SelectSearchLivewire extends Component {
    // Core component properties
    public string $name      = '';

    public string $wireModel = '';

    public ?string $label    = null;

    public ?string $selected = null;

    public bool $required    = false;

    public bool $disabled    = false;

    public bool $hidden      = false;

    // Search configuration
    public ?string $searchModel     = null;

    public ?string $searchColumn    = null;

    public ?string $searchDependsOn = null;

    public array $searchOption      = ['id', 'name'];

    // UI text properties
    public ?string $textDefault      = null;

    public ?string $textLoading      = null;

    public ?string $textError        = null;

    public ?string $textNotFound     = null;

    public ?string $textPleaseSelect = null;

    // State management
    public array $options          = [];

    public bool $isLoading         = false;

    public bool $hasError          = false;

    public string $dependsValue    = '';

    public ?string $errorMessage   = null;

    public bool $usePendingFilters = false;

    // Performance optimization flags
    private bool $isLoadingOptions       = false;

    private bool $hasLoadedInThisRequest = false;

    // Injected services
    protected ?CacheService $cacheService        = null;

    protected ?OptionLoaderService $optionLoader = null;

    public function mount(
        string $name,
        string $wireModel = '',
        ?string $label = null,
        ?string $selected = null,
        bool $required = false,
        bool $disabled = false,
        bool $hidden = false,
        ?string $searchModel = null,
        ?string $searchColumn = null,
        ?string $searchDependsOn = null,
        array $searchOption = ['id', 'name'],
        ?string $textDefault = null,
        ?string $textLoading = null,
        ?string $textError = null,
        ?string $textNotFound = null,
        ?string $textPleaseSelect = null,
        bool $usePendingFilters = false
    ) {
        $this->initializeServices();
        $this->setBasicProperties($name, $wireModel, $label, $selected, $required, $disabled, $hidden, $usePendingFilters);
        $this->setSearchConfiguration($searchModel, $searchColumn, $searchDependsOn, $searchOption);
        $this->setTextMessages($textDefault, $textLoading, $textError, $textNotFound, $textPleaseSelect);
        $this->initializeComponent();
    }

    /**
     * Initialize required services
     */
    protected function initializeServices(): void {
        $this->cacheService = app(CacheService::class);
        $this->optionLoader = app(OptionLoaderService::class);
    }

    /**
     * Get cache service with lazy initialization
     */
    protected function getCacheService(): CacheService {
        if (!$this->cacheService) {
            $this->cacheService = app(CacheService::class);
        }

        return $this->cacheService;
    }

    /**
     * Get option loader service with lazy initialization
     */
    protected function getOptionLoader(): OptionLoaderService {
        if (!$this->optionLoader) {
            $this->optionLoader = app(OptionLoaderService::class);
        }

        return $this->optionLoader;
    }

    /**
     * Set basic component properties
     */
    protected function setBasicProperties(
        string $name,
        string $wireModel,
        ?string $label,
        ?string $selected,
        bool $required,
        bool $disabled,
        bool $hidden,
        bool $usePendingFilters
    ): void {
        $this->name              = $name;
        $this->wireModel         = $wireModel ?: ($usePendingFilters ? "pendingFilters.{$name}" : "filters.{$name}");
        $this->label             = $label;
        $this->selected          = $selected ?? '';
        $this->required          = $required;
        $this->disabled          = $disabled;
        $this->hidden            = $hidden;
        $this->usePendingFilters = $usePendingFilters;
    }

    /**
     * Set search configuration properties
     */
    protected function setSearchConfiguration(?string $searchModel, ?string $searchColumn, ?string $searchDependsOn, array $searchOption): void {
        $this->searchModel     = $searchModel;
        $this->searchColumn    = $searchColumn;
        $this->searchDependsOn = $searchDependsOn;
        $this->searchOption    = $searchOption;
    }

    /**
     * Set text message properties with defaults
     */
    protected function setTextMessages(?string $textDefault, ?string $textLoading, ?string $textError, ?string $textNotFound, ?string $textPleaseSelect): void {
        $this->textDefault      = $textDefault ?: ($this->searchDependsOn ? 'Please select a parent option first' : DatatableConstants::getPleaseSelectMessage());
        $this->textLoading      = $textLoading ?: DatatableConstants::getLoadingMessage();
        $this->textError        = $textError ?: DatatableConstants::getErrorMessage();
        $this->textNotFound     = $textNotFound ?: DatatableConstants::getNotFoundMessage();
        $this->textPleaseSelect = $textPleaseSelect ?: DatatableConstants::getPleaseSelectMessage();
    }

    /**
     * Initialize component state
     */
    protected function initializeComponent(): void {
        if (!$this->searchDependsOn) {
            $this->loadOptions();
        } else {
            $this->disabled = true;
            $this->options  = [];
            $this->dispatch('syncFieldValue', $this->name);
        }
    }

    #[On('refreshSelectSearch')]
    public function loadOptions() {
        if (!$this->canLoadOptions()) {
            return;
        }

        if ($this->searchDependsOn && empty($this->dependsValue)) {
            $this->resetToDisabledState();

            return;
        }

        if ($this->shouldSkipLoad()) {
            return;
        }

        $this->setLoadingState();

        try {
            $this->options = $this->getOptionLoader()->loadOptions(
                $this->getLoadConfig()
            );
            $this->onLoadSuccess();
        } catch (Throwable $e) {
            $this->onLoadError($e);
        } finally {
            $this->clearLoadingState();
        }
    }

    /**
     * Check if options can be loaded
     */
    protected function canLoadOptions(): bool {
        return !empty($this->searchModel) && !empty($this->searchColumn);
    }

    /**
     * Reset component to disabled state
     */
    protected function resetToDisabledState(): void {
        $this->options  = [];
        $this->disabled = true;
    }

    /**
     * Check if load should be skipped
     */
    protected function shouldSkipLoad(): bool {
        return $this->isLoadingOptions || $this->hasLoadedInThisRequest;
    }

    /**
     * Set loading state
     */
    protected function setLoadingState(): void {
        $this->isLoadingOptions = true;
        $this->hasError         = false;
        $this->disabled         = true;
    }

    /**
     * Get configuration for option loading
     */
    protected function getLoadConfig(): array {
        return [
            'model'         => $this->searchModel,
            'search_column' => $this->searchColumn,
            'depends_on'    => $this->searchDependsOn,
            'depends_value' => $this->dependsValue,
            'search_option' => $this->searchOption,
            'cache_key'     => $this->generateCacheKey(),
        ];
    }

    /**
     * Handle successful option load
     */
    protected function onLoadSuccess(): void {
        $this->selected               = '';
        $this->disabled               = false;
        $this->hasError               = false;
        $this->hasLoadedInThisRequest = true;
    }

    /**
     * Handle option load error
     */
    protected function onLoadError(Throwable $e): void {
        $this->hasError     = true;
        $this->errorMessage = $e->getMessage();
        $this->options      = [];
        $this->selected     = '';
    }

    /**
     * Clear loading state
     */
    protected function clearLoadingState(): void {
        $this->isLoadingOptions = false;
    }

    /**
     * Generate cache key for options
     */
    private function generateCacheKey(): string {
        return $this->getCacheService()->generateKey([
            'select_search_livewire',
            $this->searchModel,
            $this->searchColumn,
            $this->searchDependsOn,
            $this->dependsValue,
            implode('_', $this->searchOption),
        ]);
    }

    /**
     * Clear dependency cache
     */
    private function clearDependencyCache(): void {
        $this->getCacheService()->forget($this->generateCacheKey());
    }

    public function updatedSelected($value) {
        if ($this->usePendingFilters) {
            $this->dispatch('selectSearchChangedPending', $this->name, $value);
        } else {
            $this->dispatch('selectSearchChanged', $this->name, $value);
        }

        // Trigger dependency updates for dependent fields
        if ($this->hasSelectSearchDependents()) {
            $this->dispatch('selectSearchDependencyChange', $this->name, $value);
        }
    }

    public function listenForParentFilters() {
        if ($this->searchDependsOn) {
            $this->dispatch('checkDependentValue', $this->searchDependsOn);
        }
    }

    #[On('fieldValueSynced')]
    public function handleFieldValueSynced($fieldName, $value) {
        if ($fieldName === $this->name) {
            $this->selected = $value ?? '';
        }
    }

    #[On('syncFromParent')]
    public function syncFromParent($fieldName, $value) {
        if ($fieldName === $this->name) {
            $this->selected = $value ?? '';
        }
    }

    #[On('cascadeDependencyUpdate')]
    public function handleCascadeDependencyUpdate($eventData) {
        $parentField = $eventData['parent_field'] ?? null;
        $parentValue = $eventData['parent_value'] ?? null;
        $resetFields = $eventData['reset_fields'] ?? [];

        // Handle direct dependency - load options for this field
        if ($parentField === $this->searchDependsOn) {
            $this->dependsValue = $parentValue;
            $this->selected     = '';

            if (empty($parentValue)) {
                $this->resetField();
            } else {
                $this->prepareForLoad();
                $this->loadOptions();
            }

            // Notify parent component
            $event = $this->usePendingFilters ? 'selectSearchChangedPending' : 'selectSearchChanged';
            $this->dispatch($event, $this->name, '');
        }
        // Handle cascade reset - just reset without events
        elseif (in_array($this->name, $resetFields)) {
            $this->resetField();
        }
    }

    #[On('resetAllSelectSearch')]
    public function handleResetAllSelectSearch() {
        $this->resetField();

        // If this field has no dependency, reload options after reset
        if (!$this->searchDependsOn) {
            $this->loadOptions();
        }

        // Notify parent component about the reset
        $event = $this->usePendingFilters ? 'selectSearchChangedPending' : 'selectSearchChanged';
        $this->dispatch($event, $this->name, '');
    }

    public function boot() {
        $this->resetLoadingFlags();

        if ($this->searchDependsOn && empty($this->dependsValue)) {
            $this->disabled = true;
            $this->options  = [];
        }
    }

    public function hydrate() {
        $this->resetLoadingFlags();

        if ($this->searchDependsOn && empty($this->dependsValue)) {
            $this->disabled = true;
            $this->options  = [];
        }
    }

    public function dehydrate() {
        if ($this->selected === null) {
            $this->selected = '';
        }
    }

    /**
     * Reset loading flags to prevent duplicate loads
     */
    private function resetLoadingFlags(): void {
        $this->isLoadingOptions       = false;
        $this->hasLoadedInThisRequest = false;
    }

    /**
     * Check if this field has dependent fields
     */
    private function hasSelectSearchDependents(): bool {
        return true; // Assume true for compatibility
    }

    /**
     * Reset field to disabled state
     */
    private function resetField(): void {
        $this->selected     = '';
        $this->options      = [];
        $this->disabled     = true;
        $this->hasError     = false;
        $this->dependsValue = '';
        $this->clearDependencyCache();
        $this->resetLoadingFlags();
    }

    /**
     * Prepare field for option loading
     */
    private function prepareForLoad(): void {
        $this->disabled = false;
        $this->options  = [];
        $this->selected = '';
        $this->clearDependencyCache();
        $this->resetLoadingFlags();
    }

    public function render() {
        return view('datatables::components.datatables.select-search-livewire');
    }
}
