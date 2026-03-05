<?php

namespace App\Datatables\Services;

use InvalidArgumentException;

use App\Datatables\Constants\DatatableConstants;

/**
 * Service for handling datatable configuration management
 */
class ConfigurationService {
    /**
     * Configuration validation rules
     */
    protected array $validationRules = [
        'perPage'             => ['required', 'integer', 'min:1', 'max:1000'],
        'defaultActions'      => ['boolean'],
        'columns'             => ['array'],
        'sortableColumns'     => ['array'],
        'groupColumns'        => ['array'],
        'filterFields'        => ['array'],
        'extraActions'        => ['array'],
        'showFilterPanel'     => ['boolean'],
        'showFilterForm'      => ['boolean'],
        'paginationRange'     => ['integer', 'min:1', 'max:10'],
        'showEllipsis'        => ['boolean'],
        'minPagesForEllipsis' => ['integer', 'min:1'],
    ];

    /**
     * Resolve configuration by merging user config with defaults
     */
    public function resolveConfiguration(array $userConfig, array $defaultConfig): array {
        $resolvedConfig = $this->mergeConfigurations($userConfig, $defaultConfig);
        $this->validateConfiguration($resolvedConfig);

        return $this->enrichConfiguration($resolvedConfig);
    }

    /**
     * Merge user configuration with defaults
     */
    protected function mergeConfigurations(array $userConfig, array $defaultConfig): array {
        $resolvedConfig = array_merge($defaultConfig, $userConfig);

        // Handle pagination configuration from global config
        $paginationConfig = config('datatables.pagination', []);
        $resolvedConfig   = array_merge($resolvedConfig, [
            'paginationRange'     => $userConfig['paginationRange'] ?? $paginationConfig['pagination_range'] ?? $defaultConfig['paginationRange'],
            'showEllipsis'        => $userConfig['showEllipsis'] ?? $paginationConfig['show_ellipsis'] ?? $defaultConfig['showEllipsis'],
            'minPagesForEllipsis' => $userConfig['minPagesForEllipsis'] ?? $paginationConfig['min_pages_for_ellipsis'] ?? $defaultConfig['minPagesForEllipsis'],
        ]);

        return $resolvedConfig;
    }

    /**
     * Validate configuration values
     */
    protected function validateConfiguration(array $config): void {
        foreach ($this->validationRules as $key => $rules) {
            if (!isset($config[$key])) {
                continue;
            }

            $value = $config[$key];
            $this->validateField($key, $value, $rules);
        }
    }

    /**
     * Validate individual field
     */
    protected function validateField(string $field, $value, array $rules): void {
        foreach ($rules as $rule) {
            if (!$this->applyValidationRule($value, $rule)) {
                throw new InvalidArgumentException("Invalid {$field} configuration: {$rule} validation failed");
            }
        }
    }

    /**
     * Apply single validation rule
     */
    protected function applyValidationRule($value, string $rule): bool {
        return match ($rule) {
            'required' => !empty($value) || $value === 0 || $value === false,
            'integer'  => is_int($value),
            'array'    => is_array($value),
            'boolean'  => is_bool($value),
            default    => $this->applyParameterizedRule($value, $rule),
        };
    }

    /**
     * Apply parameterized validation rules (min:1, max:100, etc.)
     */
    protected function applyParameterizedRule($value, string $rule): bool {
        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);

            return is_numeric($value) && $value >= $min;
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);

            return is_numeric($value) && $value <= $max;
        }

        return true; // Unknown rules pass
    }

    /**
     * Enrich configuration with computed values
     */
    protected function enrichConfiguration(array $config): array {
        // Ensure perPage is within valid options
        $pageOptions = DatatableConstants::getPageSizeOptions();
        if (!in_array($config['perPage'], $pageOptions)) {
            $config['perPage'] = $this->findClosestPageSize($config['perPage'], $pageOptions);
        }

        // Ensure string values are properly set
        $config['emptyMessage'] = $config['emptyMessage'] ?: DatatableConstants::getEmptyMessage();
        $config['routeName']    = $config['routeName'] ?: '';

        return $config;
    }

    /**
     * Find closest valid page size
     */
    protected function findClosestPageSize(int $requested, array $options): int {
        $closest = $options[0];
        $minDiff = abs($requested - $closest);

        foreach ($options as $option) {
            $diff = abs($requested - $option);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $closest = $option;
            }
        }

        return $closest;
    }

    /**
     * Get configuration schema for validation
     */
    public function getConfigurationSchema(): array {
        return $this->validationRules;
    }
}
