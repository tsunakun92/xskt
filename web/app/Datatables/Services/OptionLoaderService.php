<?php

namespace App\Datatables\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

use Modules\Logging\Utils\LogHandler;

/**
 * Service for loading select options from models
 */
class OptionLoaderService {
    protected CacheService $cacheService;

    public function __construct(CacheService $cacheService) {
        $this->cacheService = $cacheService;
    }

    /**
     * Load options with caching support
     */
    public function loadOptions(array $config): array {
        $this->validateConfig($config);

        if ($this->cacheService->isEnabled()) {
            return $this->cacheService->remember(
                $config['cache_key'],
                fn() => $this->fetchOptionsFromDatabase($config)
            );
        }

        return $this->fetchOptionsFromDatabase($config);
    }

    /**
     * Validate configuration
     */
    protected function validateConfig(array $config): void {
        $required = ['model', 'search_option'];

        foreach ($required as $key) {
            if (!isset($config[$key]) || empty($config[$key])) {
                throw new InvalidArgumentException("Missing required config: {$key}");
            }
        }

        if (!class_exists($config['model'])) {
            throw new InvalidArgumentException("Model class {$config['model']} does not exist");
        }
    }

    /**
     * Fetch options from database
     */
    protected function fetchOptionsFromDatabase(array $config): array {
        try {
            $model = $this->createModelInstance($config['model']);
            $query = $this->buildQuery($model, $config);

            [$valueColumn, $displayColumn] = $config['search_option'];

            $results = $query->pluck($displayColumn, $valueColumn)->toArray();

            // Log successful load for debugging
            LogHandler::debug('Options loaded successfully', [
                'model'          => $config['model'],
                'count'          => count($results),
                'has_dependency' => !empty($config['depends_on']),
            ]);

            return $results;
        } catch (Throwable $e) {
            LogHandler::error('Failed to fetch options from database', [
                'model'  => $config['model'],
                'error'  => $e->getMessage(),
                'config' => $config,
            ]);

            throw new RuntimeException('Failed to load options: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create model instance with validation
     */
    protected function createModelInstance(string $modelClass): Model {
        if (!class_exists($modelClass)) {
            throw new InvalidArgumentException("Model class {$modelClass} not found");
        }

        $model = new $modelClass;

        if (!$model instanceof Model) {
            throw new InvalidArgumentException("Class {$modelClass} is not an Eloquent model");
        }

        return $model;
    }

    /**
     * Build query for options
     */
    protected function buildQuery(Model $model, array $config): Builder {
        $query = $model->newQuery();

        // Apply dependency filter if configured
        if (!empty($config['depends_on']) && !empty($config['search_column']) && !empty($config['depends_value'])) {
            $query->where($config['search_column'], $config['depends_value']);
        }

        // Apply default ordering if available
        if (method_exists($model, 'getDefaultOrderColumn')) {
            $orderColumn    = $model->getDefaultOrderColumn();
            $orderDirection = method_exists($model, 'getDefaultOrderDirection')
            ? $model->getDefaultOrderDirection()
            : 'asc';
            $query->orderBy($orderColumn, $orderDirection);
        } else {
            // Default ordering by display column
            [$valueColumn, $displayColumn] = $config['search_option'];
            $query->orderBy($displayColumn);
        }

        // Apply any model-specific scopes
        if (method_exists($model, 'scopeForSelectSearch')) {
            $query->forSelectSearch();
        }

        return $query;
    }

    /**
     * Load options with custom query builder
     */
    public function loadOptionsWithQuery(string $modelClass, callable $queryBuilder, array $searchOption, ?string $cacheKey = null): array {
        $config = [
            'model'         => $modelClass,
            'search_option' => $searchOption,
            'cache_key'     => $cacheKey ?? $this->cacheService->generateKey(['custom', $modelClass, md5(serialize($searchOption))]),
        ];

        if ($this->cacheService->isEnabled() && $cacheKey) {
            return $this->cacheService->remember(
                $config['cache_key'],
                fn() => $this->fetchOptionsWithCustomQuery($modelClass, $queryBuilder, $searchOption)
            );
        }

        return $this->fetchOptionsWithCustomQuery($modelClass, $queryBuilder, $searchOption);
    }

    /**
     * Fetch options with custom query
     */
    protected function fetchOptionsWithCustomQuery(string $modelClass, callable $queryBuilder, array $searchOption): array {
        try {
            $model = $this->createModelInstance($modelClass);
            $query = $queryBuilder($model->newQuery());

            if (!$query instanceof Builder) {
                throw new InvalidArgumentException('Query builder must return a Builder instance');
            }

            [$valueColumn, $displayColumn] = $searchOption;

            return $query->pluck($displayColumn, $valueColumn)->toArray();
        } catch (Throwable $e) {
            LogHandler::error('Failed to fetch options with custom query', [
                'model' => $modelClass,
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('Failed to load options with custom query: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Preload options for multiple configurations
     */
    public function preloadOptions(array $configs): array {
        $results = [];

        foreach ($configs as $key => $config) {
            try {
                $results[$key] = $this->loadOptions($config);
            } catch (Throwable $e) {
                LogHandler::warning("Failed to preload options for {$key}", [
                    'error'  => $e->getMessage(),
                    'config' => $config,
                ]);
                $results[$key] = [];
            }
        }

        return $results;
    }

    /**
     * Clear cache for specific model
     */
    public function clearModelCache(string $modelClass): int {
        $pattern = "select_search_livewire_{$modelClass}_*";

        return $this->cacheService->forgetByPattern($pattern);
    }

    /**
     * Get load statistics
     */
    public function getLoadStats(array $config): array {
        return [
            'model'          => $config['model'],
            'cache_enabled'  => $this->cacheService->isEnabled(),
            'cache_key'      => $config['cache_key'] ?? null,
            'has_dependency' => !empty($config['depends_on']),
            'search_columns' => $config['search_option'],
        ];
    }
}
