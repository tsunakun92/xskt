<?php

namespace App\Datatables\Traits;

use Exception;

use App\Datatables\Constants\DatatableConstants;

/**
 * Common utilities and helper methods for datatable components
 *
 * Provides reusable functionality for:
 * - Pagination handling
 * - Filter management
 * - Loading states
 * - Value formatting
 */
trait DatatableHelperTrait {
    /**
     * Execute callback with pagination reset
     * Loading state management is optional to prevent UI flicker
     */
    protected function withLoadingAndReset(callable $callback): void {
        $callback();
        $this->resetPageSafely();
    }

    /**
     * Execute callback with conditional pagination reset
     * Only resets pagination if filters have actually changed
     */
    protected function withConditionalReset(callable $callback, array $previousFilters = []): void {
        $callback();

        // Only reset pagination if filters have actually changed
        if ($this->filtersHaveChanged($previousFilters)) {
            $this->resetPageSafely();
        }
    }

    /**
     * Check if filters have actually changed
     */
    protected function filtersHaveChanged(array $previousFilters): bool {
        $currentFilters = $this->filters ?? [];

        // Compare filter arrays
        if (count($currentFilters) !== count($previousFilters)) {
            return true;
        }

        foreach ($currentFilters as $key => $value) {
            if (!isset($previousFilters[$key]) || $previousFilters[$key] !== $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set loading state if supported by component
     */
    protected function setLoadingState(bool $loading): void {
        if (method_exists($this, 'setLoading') && config('datatables.performance.enable_loading_states', false)) {
            $this->setLoading($loading);
        }
    }

    /**
     * Reset pagination safely
     */
    protected function resetPageSafely(): void {
        try {
            $this->resetPage();
        } catch (Exception $e) {
            // Silently handle pagination reset errors
        }
    }

    /**
     * Get current page number
     */
    public function getCurrentPage(): int {
        return $this->getPage() ?? 1;
    }

    /**
     * Get per-page options
     */
    public function getPerPageOptions(): array {
        return DatatableConstants::getPageSizeOptions();
    }

    /**
     * Check if filters are applied
     */
    public function hasFilters(): bool {
        return !empty(array_filter($this->filters));
    }

    /**
     * Get active filters count (non-empty values)
     */
    public function getActiveFiltersCount(): int {
        return count(array_filter($this->filters, fn($value) => !$this->isEmptyFilterValue($value)));
    }

    /**
     * Check if filter value is considered empty
     */
    protected function isEmptyFilterValue(mixed $value): bool {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Get filter value with type safety
     */
    public function getFilterValue(string $field, mixed $default = null): mixed {
        return $this->filters[$field] ?? $default;
    }

    /**
     * Set filter value with validation
     */
    public function setFilterValue(string $field, mixed $value): void {
        // Validate field exists in filter fields configuration
        if (property_exists($this, 'filterFields') && !empty($this->filterFields)) {
            if (!array_key_exists($field, $this->filterFields)) {
                return; // Skip unknown fields
            }
        }

        $this->filters[$field] = $value;
    }

    /**
     * Check if field has a non-empty filter value
     */
    public function hasFilterValue(string $field): bool {
        return !$this->isEmptyFilterValue($this->filters[$field] ?? null);
    }

    /**
     * Clear specific filter
     */
    public function clearFilter(string $field): void {
        unset($this->filters[$field]);
    }

    /**
     * Get filter summary for debugging
     */
    public function getFilterSummary(): array {
        return [
            'total_filters'  => count($this->filters),
            'active_filters' => $this->getActiveFiltersCount(),
            'filter_keys'    => array_keys($this->filters),
            'active_keys'    => array_keys(array_filter($this->filters, fn($value) => !$this->isEmptyFilterValue($value))),
        ];
    }

    /**
     * Format column value for display
     */
    protected function formatColumnValue(mixed $value, string $column): string {
        return $value === null || $value === '' ? '-' : (string) $value;
    }

    /**
     * Get column label
     */
    protected function getColumnLabel(string $column): string {
        return $this->columns[$column] ?? ucfirst(str_replace('_', ' ', $column));
    }
}
