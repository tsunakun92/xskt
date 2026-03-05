<?php

namespace App\Models\Traits;

use App\Utils\CacheHandler;

/**
 * ModelCacheTrait
 *
 * Shared cache helpers for Eloquent models using CacheHandler.
 * Provides:
 * - rememberCache / forgetCache / forgetCachePattern helpers
 * - automatic cache clear on create/update/delete based on patterns
 *
 * Models can override getCacheClearPatterns() to declare patterns.
 * Use {id} placeholder to inject primary key value.
 */
trait ModelCacheTrait {
    /**
     * Toggle model-level cache. Override const ENABLE_MODEL_CACHE in model to disable.
     */
    protected const ENABLE_MODEL_CACHE = true;

    /**
     * Boot the trait: clear declared cache patterns on lifecycle events.
     *
     * @return void
     */
    protected static function bootModelCacheTrait(): void {
        static::created(function ($model) {
            $model->clearModelCache();
        });

        static::updated(function ($model) {
            $model->clearModelCache();
        });

        static::deleted(function ($model) {
            $model->clearModelCache();
        });
    }

    /**
     * Check if model cache is enabled (can be overridden via const ENABLE_MODEL_CACHE).
     *
     * @return bool
     */
    protected static function isModelCacheEnabled(): bool {
        if (defined(static::class . '::ENABLE_MODEL_CACHE')) {
            return (bool) constant(static::class . '::ENABLE_MODEL_CACHE');
        }

        return static::ENABLE_MODEL_CACHE;
    }

    /**
     * Remember helper using CacheHandler.
     *
     * @param  string  $key
     * @param  callable  $callback
     * @param  int|null  $ttl
     * @param  string  $type
     * @return mixed
     */
    protected static function rememberCache(string $key, callable $callback, ?int $ttl = null, string $type = CacheHandler::TYPE_STATIC) {
        if (!static::isModelCacheEnabled()) {
            return $callback();
        }

        return CacheHandler::remember($key, $callback, $ttl, $type);
    }

    /**
     * Forget cache key helper.
     *
     * @param  string  $key
     * @param  string  $type
     * @return bool
     */
    protected static function forgetCache(string $key, string $type = CacheHandler::TYPE_STATIC): bool {
        if (!static::isModelCacheEnabled()) {
            return false;
        }

        return CacheHandler::forget($key, $type);
    }

    /**
     * Forget cache by pattern helper.
     *
     * @param  string  $pattern
     * @return int
     */
    protected static function forgetCachePattern(string $pattern): int {
        if (!static::isModelCacheEnabled()) {
            return 0;
        }

        return CacheHandler::forgetByPattern($pattern);
    }

    /**
     * Clear cache patterns declared by model.
     *
     * @return void
     */
    public function clearModelCache(): void {
        if (!static::isModelCacheEnabled()) {
            return;
        }

        $patterns = $this->getCacheClearPatterns();

        if (empty($patterns)) {
            return;
        }

        $placeholders = [
            '{id}' => $this->getKey(),
        ];

        foreach ($patterns as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }
            $resolved = strtr($pattern, $placeholders);
            CacheHandler::forgetByPattern($resolved);
        }
    }

    /**
     * Declare cache patterns to clear on create/update/delete.
     * Use {id} placeholder for model primary key if needed.
     *
     * @return array<int, string>
     */
    public function getCacheClearPatterns(): array {
        return [];
    }
}
