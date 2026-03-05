<?php

namespace App\Datatables\Traits;

/**
 * Sorting functionality for datatable components
 */
trait SortableTrait {
    /**
     * Sort by column with direction
     */
    public function sortByColumn(string $column, string $direction = 'asc'): void {
        if (!$this->isSortable($column)) {
            return;
        }

        $previousSortBy        = $this->sortBy;
        $previousSortDirection = $this->sortDirection;

        $this->withLoadingAndReset(function () use ($column, $direction) {
            $this->sortBy        = $column;
            $this->sortDirection = in_array($direction, ['asc', 'desc']) ? $direction : 'asc';

            // Create session key if needed and save to session
            if (method_exists($this, 'createSessionKeyIfNeeded')) {
                $this->createSessionKeyIfNeeded();
            }
            if (method_exists($this, 'saveFilterStateToSession')) {
                $this->saveFilterStateToSession();
            }
        });
    }

    /**
     * Toggle sort direction for column
     */
    public function toggleSort(string $column): void {
        $previousSortBy        = $this->sortBy;
        $previousSortDirection = $this->sortDirection;

        $this->withLoadingAndReset(function () use ($column) {
            if ($this->sortBy === $column) {
                $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                $this->sortBy        = $column;
                $this->sortDirection = 'asc';
            }

            // Create session key if needed and save to session
            if (method_exists($this, 'createSessionKeyIfNeeded')) {
                $this->createSessionKeyIfNeeded();
            }
            if (method_exists($this, 'saveFilterStateToSession')) {
                $this->saveFilterStateToSession();
            }
        });
    }

    /**
     * Check if column is currently sorted
     */
    public function isSorted(string $column): bool {
        return $this->sortBy === $column;
    }

    /**
     * Get sort direction for column
     */
    public function getSortDirection(string $column): string {
        return $this->isSorted($column) ? $this->sortDirection : '';
    }

    /**
     * Check if column is sortable
     */
    public function isSortable(string $column): bool {
        return in_array($column, $this->sortableColumns);
    }
}
