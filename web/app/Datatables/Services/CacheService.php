<?php

namespace App\Datatables\Services;

use Illuminate\Support\Facades\Cache;
use Throwable;

use Modules\Logging\Utils\LogHandler;

/**
 * Service for handling caching operations in datatables
 */
class CacheService {
    /**
     * Cache configuration
     */
    protected array $config;

    public function __construct() {
        $this->config = config('datatables.cache', [
            'enabled' => true,
            'ttl'     => 300,
        ]);
    }

    /**
     * Generate cache key from parts
     */
    public function generateKey(array $keyParts): string {
        $filteredParts = array_filter($keyParts, fn($part) => !empty($part));

        return implode('_', $filteredParts);
    }

    /**
     * Remember value in cache with error handling
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed {
        if (!$this->isEnabled()) {
            return $callback();
        }

        try {
            $ttl = $ttl ?? $this->config['ttl'];

            return Cache::remember($key, $ttl, $callback);
        } catch (Throwable $e) {
            LogHandler::cache('Cache remember failed', [
                'key'   => $key,
                'error' => $e->getMessage(),
            ]);

            // Fallback to direct callback execution
            return $callback();
        }
    }

    /**
     * Store value in cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $ttl = $ttl ?? $this->config['ttl'];

            return Cache::put($key, $value, $ttl);
        } catch (Throwable $e) {
            LogHandler::cache('Cache put failed', [
                'key'   => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get value from cache
     */
    public function get(string $key, mixed $default = null): mixed {
        if (!$this->isEnabled()) {
            return $default;
        }

        try {
            return Cache::get($key, $default);
        } catch (Throwable $e) {
            LogHandler::cache('Cache get failed', [
                'key'   => $key,
                'error' => $e->getMessage(),
            ]);

            return $default;
        }
    }

    /**
     * Remove value from cache
     */
    public function forget(string $key): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            return Cache::forget($key);
        } catch (Throwable $e) {
            LogHandler::cache('Cache forget failed', [
                'key'   => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clear cache by pattern
     */
    public function forgetByPattern(string $pattern): int {
        if (!$this->isEnabled()) {
            return 0;
        }

        try {
            $keys    = $this->getKeysByPattern($pattern);
            $cleared = 0;

            foreach ($keys as $key) {
                if (Cache::forget($key)) {
                    $cleared++;
                }
            }

            return $cleared;
        } catch (Throwable $e) {
            LogHandler::cache('Cache pattern forget failed', [
                'pattern' => $pattern,
                'error'   => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Check if caching is enabled
     */
    public function isEnabled(): bool {
        return $this->config['enabled'] ?? true;
    }

    /**
     * Get cache TTL
     */
    public function getTtl(): int {
        return $this->config['ttl'] ?? 300;
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array {
        try {
            // This would depend on the cache driver
            // For Redis/Memcached this could return actual stats
            return [
                'enabled' => $this->isEnabled(),
                'ttl'     => $this->getTtl(),
                'driver'  => config('cache.default'),
            ];
        } catch (Throwable $e) {
            return [
                'enabled' => $this->isEnabled(),
                'ttl'     => $this->getTtl(),
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Get keys matching pattern (implementation depends on cache driver)
     */
    protected function getKeysByPattern(string $pattern): array {
        // This is a simplified implementation
        // In a real application, this would depend on the cache driver
        // Redis: KEYS command, Memcached: requires key tracking
        return [];
    }

    /**
     * Flush all datatables cache
     */
    public function flushDatatablesCache(): int {
        return $this->forgetByPattern('select_search_livewire*');
    }

    /**
     * Get cache key info
     */
    public function getKeyInfo(string $key): array {
        return [
            'key'    => $key,
            'exists' => $this->has($key),
            'size'   => $this->getKeySize($key),
        ];
    }

    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            return Cache::has($key);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get estimated size of cached value
     */
    protected function getKeySize(string $key): int {
        try {
            $value = $this->get($key);

            return $value ? strlen(serialize($value)) : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }
}
