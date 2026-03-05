<?php

namespace App\Datatables\Contracts;

use Throwable;

/**
 * Interface for datatable configuration management
 */
interface DatatableConfigInterface {
    /**
     * Get default configuration for the component
     */
    public function getDefaultConfig(): array;

    /**
     * Apply configuration to component properties
     */
    public function applyConfiguration(array $config): void;

    /**
     * Set error state on the component
     */
    public function setError(string $message, ?Throwable $exception = null): void;

    /**
     * Clear error state from the component
     */
    public function clearError(): void;
}
