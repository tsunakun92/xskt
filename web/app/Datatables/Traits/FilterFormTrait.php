<?php

namespace App\Datatables\Traits;

use Livewire\Attributes\On;

/**
 * Filter form handling logic for datatable components
 */
trait FilterFormTrait {
    /**
     * Handle select-search field changes
     */
    #[On('selectSearchChanged')]
    public function handleSelectSearchChange($fieldName, $value): void {
        $previousFilters = $this->filters;
        $this->withConditionalReset(function () use ($fieldName, $value) {
            $this->filters[$fieldName] = $value;
            $this->resetDependentFields($fieldName);
            $this->dispatch('dependentFieldChanged', $fieldName, $value);

            // Create session key if needed and save to session
            if (method_exists($this, 'createSessionKeyIfNeeded')) {
                $this->createSessionKeyIfNeeded();
            }
            if (method_exists($this, 'saveFilterStateToSession')) {
                $this->saveFilterStateToSession();
            }
        }, $previousFilters);
    }

    /**
     * Handle select-search field changes for pending filters
     */
    #[On('selectSearchChangedPending')]
    public function handleSelectSearchChangePending($fieldName, $value): void {
        $this->pendingFilters[$fieldName] = $value;
        $this->resetDependentPendingFields($fieldName);
    }

    /**
     * Check if a dependent field has a value and notify child components
     */
    #[On('checkDependentValue')]
    public function checkDependentValue($dependentField): void {
        $value = $this->filters[$dependentField] ?? null;
        if ($value) {
            $this->dispatch('dependentFieldChanged', $dependentField, $value);
        }
    }

    /**
     * Sync field value with child components
     */
    #[On('syncFieldValue')]
    public function syncFieldValue($fieldName): void {
        $value = $this->filters[$fieldName] ?? null;
        $this->dispatch('fieldValueSynced', $fieldName, $value);
        $this->dispatch('syncFromParent', $fieldName, $value);
    }

    /**
     * Reset dependent filter fields when a parent field changes
     */
    protected function resetDependentFields(string $changedField): void {
        $dependentFields = $this->getDependentFields($changedField);

        if (empty($dependentFields)) {
            return;
        }

        foreach ($dependentFields as $dependentField) {
            $this->filters[$dependentField] = '';
        }

        if (!empty($dependentFields)) {
            $this->dispatch('dependentFieldsReset', [
                'parent_field' => $changedField,
                'reset_fields' => $dependentFields,
                'timestamp'    => now()->timestamp,
            ]);
        }
    }

    /**
     * Reset dependent pending filter fields when a parent field changes (for submit-based filtering)
     */
    protected function resetDependentPendingFields(string $changedField): void {
        $dependentFields = $this->getDependentFields($changedField);

        if (empty($dependentFields)) {
            return;
        }

        foreach ($dependentFields as $dependentField) {
            $this->pendingFilters[$dependentField] = '';
        }

        if (!empty($dependentFields)) {
            $this->dispatch('dependentFieldsReset', [
                'parent_field' => $changedField,
                'reset_fields' => $dependentFields,
                'timestamp'    => now()->timestamp,
            ]);
        }
    }

    /**
     * Get all fields that depend on the specified field
     */
    protected function getDependentFields(string $parentField): array {
        $dependentFields = [];

        foreach ($this->filterFields as $fieldName => $config) {
            if (isset($config['search_depends_on']) && $config['search_depends_on'] === $parentField) {
                $dependentFields[] = $fieldName;
            }
        }

        return $dependentFields;
    }

    /**
     * Check if a field has any dependencies (other fields depend on it)
     */
    protected function hasDependentFields(string $fieldName): bool {
        return !empty($this->getDependentFields($fieldName));
    }

    /**
     * Reset all cascade dependencies in one operation
     */
    protected function resetAllCascadeDependencies(string $rootField, $rootValue = null): void {
        $allDependents = $this->getAllCascadeDependents($rootField);

        if (empty($allDependents)) {
            return;
        }

        // Reset all dependent fields
        foreach ($allDependents as $dependentField) {
            $this->pendingFilters[$dependentField] = '';
        }

        // Send single combined event
        $this->dispatch('cascadeDependencyUpdate', [
            'parent_field' => $rootField,
            'parent_value' => $rootValue,
            'reset_fields' => $allDependents,
            'timestamp'    => now()->timestamp,
        ]);
    }

    /**
     * Get all cascade dependent fields (including nested dependencies)
     */
    protected function getAllCascadeDependents(string $rootField): array {
        $allDependents = [];
        $toProcess     = [$rootField];
        $processed     = [];

        while (!empty($toProcess)) {
            $currentField = array_shift($toProcess);

            if (in_array($currentField, $processed)) {
                continue; // Prevent loops
            }

            $processed[]      = $currentField;
            $directDependents = $this->getDependentFields($currentField);

            foreach ($directDependents as $dependent) {
                if (!in_array($dependent, $allDependents)) {
                    $allDependents[] = $dependent;
                    $toProcess[]     = $dependent; // Process nested
                }
            }
        }

        return $allDependents;
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void {
        $this->withLoadingAndReset(function () {
            $this->filters        = [];
            $this->pendingFilters = [];

            // Clear range date sub-fields
            foreach ($this->filterFields as $fieldName => $config) {
                if (($config['type'] ?? null) === 'range-date') {
                    $this->filters[$fieldName . '_from']        = '';
                    $this->filters[$fieldName . '_to']          = '';
                    $this->pendingFilters[$fieldName . '_from'] = '';
                    $this->pendingFilters[$fieldName . '_to']   = '';
                }
            }

            // Reset to default sort and perPage
            $this->sortBy        = 'id';
            $this->sortDirection = 'desc';
            $this->perPage       = 10;

            // Clear session key since no filters are applied
            if (method_exists($this, 'clearSessionKey')) {
                $this->clearSessionKey();
            }

            // Send reset event to all select search components
            $this->dispatch('resetAllSelectSearch');
        });
    }

    /**
     * Parse range date values from existing filters on component mount/hydration
     */
    public function initializeRangeDateFilters(): void {
        foreach ($this->filterFields as $fieldName => $config) {
            if (($config['type'] ?? null) === 'range-date' && isset($this->filters[$fieldName])) {
                $this->parseRangeDateValue($fieldName, $this->filters[$fieldName]);
            }
        }
    }

    /**
     * Parse a combined range date value back into from/to components
     */
    protected function parseRangeDateValue(string $fieldName, string $value): void {
        if (empty($value)) {
            return;
        }

        $dates    = explode(',', $value);
        $fromDate = isset($dates[0]) && $dates[0] !== 'null' ? $dates[0] : '';
        $toDate   = isset($dates[1]) && $dates[1] !== 'null' ? $dates[1] : '';

        $this->filters[$fieldName . '_from'] = $fromDate;
        $this->filters[$fieldName . '_to']   = $toDate;
    }

    /**
     * Update range date filter by combining from/to values
     */
    public function updateRangeDate(string $fieldName): void {
        $fromValue = $this->filters[$fieldName . '_from'] ?? '';
        $toValue   = $this->filters[$fieldName . '_to'] ?? '';

        // Combine the values in the expected "from,to" format
        if (!empty($fromValue) || !empty($toValue)) {
            $this->filters[$fieldName] = ($fromValue ?: 'null') . ',' . ($toValue ?: 'null');
        } else {
            $this->filters[$fieldName] = '';
        }

        // Trigger the normal filter update process
        $this->updatedFilters($this->filters[$fieldName], $fieldName);
    }

    /**
     * Update pending range date filter by combining from/to values (for submit-based filtering)
     */
    public function updatePendingRangeDate(string $fieldName): void {
        $fromValue = $this->pendingFilters[$fieldName . '_from'] ?? '';
        $toValue   = $this->pendingFilters[$fieldName . '_to'] ?? '';

        // Combine the values in the expected "from,to" format
        if (!empty($fromValue) || !empty($toValue)) {
            $this->pendingFilters[$fieldName] = ($fromValue ?: 'null') . ',' . ($toValue ?: 'null');
        } else {
            $this->pendingFilters[$fieldName] = '';
        }
    }

    /**
     * Update filters hook - resets pagination and handles dependent fields
     */
    public function updatedFilters($value, $field): void {
        // Skip processing for _from/_to sub-fields
        if (str_ends_with($field, '_from') || str_ends_with($field, '_to')) {
            return;
        }

        $previousFilters = $this->filters;
        $this->withConditionalReset(function () use ($field, $value) {
            $this->resetDependentFields($field);
            $this->dispatch('dependentFieldChanged', $field, $value);

            // Create session key if needed and save to session
            if (method_exists($this, 'createSessionKeyIfNeeded')) {
                $this->createSessionKeyIfNeeded();
            }
            if (method_exists($this, 'saveFilterStateToSession')) {
                $this->saveFilterStateToSession();
            }
        }, $previousFilters);
    }

    /**
     * Handle select-search to select-search dependency changes
     */
    #[On('selectSearchDependencyChange')]
    public function handleSelectSearchDependencyChange($fieldName, $value): void {
        // Handle select-search → select-search dependencies
        if ($this->hasDependentFields($fieldName)) {
            $this->resetAllCascadeDependencies($fieldName, $value);
        }
    }

    /**
     * Handle immediate dependency updates without triggering table filtering
     */
    public function updateDependencyOnly(string $fieldName, $value): void {
        $this->pendingFilters[$fieldName] = $value;

        // Process dependent fields if any exist
        if ($this->hasDependentFields($fieldName)) {
            $this->resetAllCascadeDependencies($fieldName, $value);
        }
    }
}
