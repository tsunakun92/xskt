<?php

namespace App\Utils;

use Exception;
use Illuminate\Support\Facades\Cache;

use Modules\Logging\Utils\LogHandler;

/**
 * Centralized cache handler for the application
 * Provides unified cache management with comprehensive logging
 *
 * Features:
 * - Static cache (request-scoped, cleared after request)
 * - Persistent cache (Laravel cache, survives requests)
 * - Comprehensive logging for all cache operations
 * - Support for cache tags and TTL
 */
class CacheHandler {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------
    /**
     * Cache type: Static (request-scoped, cleared after request)
     */
    public const TYPE_STATIC = 'static';

    /**
     * Cache type: Persistent (Laravel cache, survives requests)
     */
    public const TYPE_PERSISTENT = 'persistent';

    /**
     * Default TTL for persistent cache (in seconds)
     */
    public const DEFAULT_TTL = 3600; // 1 hour

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------
    /**
     * Static cache storage (request-scoped)
     *
     * @var array<string, mixed>
     */
    protected static array $staticCache = [];

    /**
     * Cache statistics for logging
     *
     * @var array<string, int>
     */
    protected static array $stats = [
        'hits'    => 0,
        'misses'  => 0,
        'sets'    => 0,
        'forgets' => 0,
        'flushes' => 0,
    ];

    /**
     * Control logging verbosity for cache hits.
     * Configured via config('cache.log_hits').
     *
     * @var bool
     */
    protected static bool $logHits = false;

    /**
     * Track keys already logged as hit within current request to avoid noisy repeats.
     *
     * @var array<string, bool>
     */
    protected static array $loggedHitKeys = [];

    /**
     * Determine if hit logging is enabled (lazy load from config).
     *
     * @return bool
     */
    protected static function shouldLogHits(): bool {
        // Cache the config value once
        static $initialized = false;
        if (!$initialized) {
            self::$logHits   = (bool) config('cache.log_hits', false);
            $initialized     = true;
        }

        return self::$logHits;
    }

    //-----------------------------------------------------
    // Public Methods - Get
    //-----------------------------------------------------
    /**
     * Get value from cache
     * Tries static cache first, then persistent cache
     *
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if key not found
     * @param  string  $type  Cache type (static or persistent)
     * @return mixed Cached value or default
     */
    public static function get(string $key, $default = null, string $type = self::TYPE_STATIC) {
        $startTime = microtime(true);

        // Try static cache first
        if (isset(self::$staticCache[$key])) {
            self::$stats['hits']++;
            $duration = (microtime(true) - $startTime) * 1000;

            // Log hit only once per key per request if enabled
            if (self::shouldLogHits() && !isset(self::$loggedHitKeys[$key])) {
                self::$loggedHitKeys[$key] = true;
                LogHandler::cache('Cache hit (static)', [
                    'key'      => $key,
                    'type'     => self::TYPE_STATIC,
                    'duration' => round($duration, 2) . 'ms',
                ]);
            }

            return self::$staticCache[$key];
        }

        // Try persistent cache if type is persistent or static cache miss
        if ($type === self::TYPE_PERSISTENT) {
            $value = Cache::get($key, $default);

            if ($value !== $default) {
                // Store in static cache for faster subsequent access
                self::$staticCache[$key] = $value;
                self::$stats['hits']++;
                $duration = (microtime(true) - $startTime) * 1000;

                // Log hit only once per key per request if enabled
                if (self::shouldLogHits() && !isset(self::$loggedHitKeys[$key])) {
                    self::$loggedHitKeys[$key] = true;
                    LogHandler::cache('Cache hit (persistent)', [
                        'key'      => $key,
                        'type'     => self::TYPE_PERSISTENT,
                        'duration' => round($duration, 2) . 'ms',
                    ]);
                }

                return $value;
            }
        }

        // Cache miss
        self::$stats['misses']++;
        $duration = (microtime(true) - $startTime) * 1000;

        LogHandler::cache('Cache miss', [
            'key'      => $key,
            'type'     => $type,
            'default'  => $default !== null,
            'duration' => round($duration, 2) . 'ms',
        ]);

        return $default;
    }

    /**
     * Get value from cache or execute callback if not found
     *
     * @param  string  $key  Cache key
     * @param  callable  $callback  Callback to execute if cache miss
     * @param  int|null  $ttl  Time to live in seconds (only for persistent cache)
     * @param  string  $type  Cache type (static or persistent)
     * @return mixed Cached value or callback result
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null, string $type = self::TYPE_STATIC) {
        $startTime = microtime(true);

        // Try to get from cache first
        $value = self::get($key, null, $type);

        if ($value !== null) {
            return $value;
        }

        // Execute callback
        try {
            $value = $callback();

            // Store in cache
            self::set($key, $value, $ttl, $type);

            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache remember (executed callback)', [
                'key'      => $key,
                'type'     => $type,
                'ttl'      => $ttl,
                'duration' => round($duration, 2) . 'ms',
            ]);

            return $value;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache remember failed (callback error)', [
                'key'      => $key,
                'type'     => $type,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            throw $e;
        }
    }

    /**
     * Check if cache key exists
     *
     * @param  string  $key  Cache key
     * @param  string  $type  Cache type (static or persistent)
     * @return bool True if key exists, false otherwise
     */
    public static function has(string $key, string $type = self::TYPE_STATIC): bool {
        // Check static cache
        if (isset(self::$staticCache[$key])) {
            LogHandler::cache('Cache has (static)', [
                'key'  => $key,
                'type' => self::TYPE_STATIC,
            ]);

            return true;
        }

        // Check persistent cache
        if ($type === self::TYPE_PERSISTENT) {
            $exists = Cache::has($key);

            LogHandler::cache('Cache has (persistent)', [
                'key'    => $key,
                'type'   => self::TYPE_PERSISTENT,
                'exists' => $exists,
            ]);

            return $exists;
        }

        return false;
    }

    //-----------------------------------------------------
    // Public Methods - Set
    //-----------------------------------------------------
    /**
     * Set value in cache
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int|null  $ttl  Time to live in seconds (only for persistent cache)
     * @param  string  $type  Cache type (static or persistent)
     * @return bool True on success, false otherwise
     */
    public static function set(string $key, $value, ?int $ttl = null, string $type = self::TYPE_STATIC): bool {
        $startTime = microtime(true);

        try {
            // Always store in static cache for faster access
            self::$staticCache[$key] = $value;
            self::$stats['sets']++;

            // Store in persistent cache if type is persistent
            if ($type === self::TYPE_PERSISTENT) {
                $ttl = $ttl ?? self::DEFAULT_TTL;
                Cache::put($key, $value, $ttl);
            }

            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache set', [
                'key'        => $key,
                'type'       => $type,
                'ttl'        => $ttl,
                'value_type' => gettype($value),
                'duration'   => round($duration, 2) . 'ms',
            ]);

            return true;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache set failed', [
                'key'      => $key,
                'type'     => $type,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            return false;
        }
    }

    /**
     * Set value in cache forever (only for persistent cache)
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @return bool True on success, false otherwise
     */
    public static function forever(string $key, $value): bool {
        $startTime = microtime(true);

        try {
            // Store in static cache
            self::$staticCache[$key] = $value;
            self::$stats['sets']++;

            // Store in persistent cache forever
            Cache::forever($key, $value);

            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache set forever', [
                'key'        => $key,
                'type'       => self::TYPE_PERSISTENT,
                'value_type' => gettype($value),
                'duration'   => round($duration, 2) . 'ms',
            ]);

            return true;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache set forever failed', [
                'key'      => $key,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            return false;
        }
    }

    //-----------------------------------------------------
    // Public Methods - Delete
    //-----------------------------------------------------
    /**
     * Forget cache key
     *
     * @param  string  $key  Cache key
     * @param  string  $type  Cache type (static or persistent)
     * @return bool True on success, false otherwise
     */
    public static function forget(string $key, string $type = self::TYPE_STATIC): bool {
        $startTime = microtime(true);

        try {
            $forgot = false;

            // Remove from static cache
            if (isset(self::$staticCache[$key])) {
                unset(self::$staticCache[$key]);
                $forgot = true;
            }

            // Remove from persistent cache if type is persistent
            if ($type === self::TYPE_PERSISTENT) {
                $forgot = Cache::forget($key) || $forgot;
            }

            if ($forgot) {
                self::$stats['forgets']++;
            }

            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache forget', [
                'key'      => $key,
                'type'     => $type,
                'success'  => $forgot,
                'duration' => round($duration, 2) . 'ms',
            ]);

            return $forgot;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache forget failed', [
                'key'      => $key,
                'type'     => $type,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            return false;
        }
    }

    /**
     * Flush all cache
     *
     * @param  string  $type  Cache type (static or persistent)
     * @return bool True on success, false otherwise
     */
    public static function flush(string $type = self::TYPE_STATIC): bool {
        $startTime = microtime(true);

        try {
            $flushed = false;

            // Flush static cache
            if ($type === self::TYPE_STATIC || $type === 'all') {
                $count             = count(self::$staticCache);
                self::$staticCache = [];
                $flushed           = true;

                LogHandler::cache('Cache flush (static)', [
                    'keys_count' => $count,
                ]);
            }

            // Flush persistent cache
            if ($type === self::TYPE_PERSISTENT || $type === 'all') {
                Cache::flush();
                $flushed = true;

                LogHandler::cache('Cache flush (persistent)', []);
            }

            if ($flushed) {
                self::$stats['flushes']++;
            }

            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache flush completed', [
                'type'     => $type,
                'duration' => round($duration, 2) . 'ms',
            ]);

            return $flushed;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache flush failed', [
                'type'     => $type,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            return false;
        }
    }

    /**
     * Forget cache keys by pattern (only for static cache)
     *
     * @param  string  $pattern  Pattern to match (supports wildcard *)
     * @return int Number of keys forgotten
     */
    public static function forgetByPattern(string $pattern): int {
        $startTime = microtime(true);
        $count     = 0;

        try {
            // Convert pattern to regex
            $escaped = preg_quote($pattern, '/');
            $regex   = '/^' . str_replace('\*', '.*', $escaped) . '$/';

            foreach (array_keys(self::$staticCache) as $key) {
                if (preg_match($regex, $key)) {
                    unset(self::$staticCache[$key]);
                    $count++;
                }
            }

            if ($count > 0) {
                self::$stats['forgets'] += $count;
            }

            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache forget by pattern', [
                'pattern'  => $pattern,
                'count'    => $count,
                'duration' => round($duration, 2) . 'ms',
            ]);

            return $count;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache forget by pattern failed', [
                'pattern'  => $pattern,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            return 0;
        }
    }

    //-----------------------------------------------------
    // Public Methods - Tags (for persistent cache)
    //-----------------------------------------------------
    /**
     * Get cache with tags (only for persistent cache)
     *
     * @param  array|string  $tags  Cache tags
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if key not found
     * @return mixed Cached value or default
     */
    public static function getTagged($tags, string $key, $default = null) {
        $startTime = microtime(true);

        try {
            $tags  = is_array($tags) ? $tags : [$tags];
            $value = Cache::tags($tags)->get($key, $default);

            $duration = (microtime(true) - $startTime) * 1000;

            if ($value !== $default) {
                // Store in static cache for faster access
                self::$staticCache[$key] = $value;
                self::$stats['hits']++;

                LogHandler::cache('Cache get tagged (hit)', [
                    'tags'     => $tags,
                    'key'      => $key,
                    'duration' => round($duration, 2) . 'ms',
                ]);
            } else {
                self::$stats['misses']++;

                LogHandler::cache('Cache get tagged (miss)', [
                    'tags'     => $tags,
                    'key'      => $key,
                    'duration' => round($duration, 2) . 'ms',
                ]);
            }

            return $value;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache get tagged failed', [
                'tags'     => $tags,
                'key'      => $key,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            return $default;
        }
    }

    /**
     * Set cache with tags (only for persistent cache)
     *
     * @param  array|string  $tags  Cache tags
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int|null  $ttl  Time to live in seconds
     * @return bool True on success, false otherwise
     */
    public static function setTagged($tags, string $key, $value, ?int $ttl = null): bool {
        $startTime = microtime(true);

        try {
            $tags = is_array($tags) ? $tags : [$tags];
            $ttl  = $ttl ?? self::DEFAULT_TTL;

            // Store in static cache
            self::$staticCache[$key] = $value;
            self::$stats['sets']++;

            // Store in persistent cache with tags
            Cache::tags($tags)->put($key, $value, $ttl);

            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache set tagged', [
                'tags'       => $tags,
                'key'        => $key,
                'ttl'        => $ttl,
                'value_type' => gettype($value),
                'duration'   => round($duration, 2) . 'ms',
            ]);

            return true;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache set tagged failed', [
                'tags'     => $tags,
                'key'      => $key,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            return false;
        }
    }

    /**
     * Flush cache by tags (only for persistent cache)
     *
     * @param  array|string  $tags  Cache tags
     * @return bool True on success, false otherwise
     */
    public static function flushTagged($tags): bool {
        $startTime = microtime(true);

        try {
            $tags = is_array($tags) ? $tags : [$tags];
            Cache::tags($tags)->flush();

            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache flush tagged', [
                'tags'     => $tags,
                'duration' => round($duration, 2) . 'ms',
            ]);

            return true;
        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            LogHandler::cache('Cache flush tagged failed', [
                'tags'     => $tags,
                'error'    => $e->getMessage(),
                'duration' => round($duration, 2) . 'ms',
            ]);

            return false;
        }
    }

    //-----------------------------------------------------
    // Public Methods - Statistics
    //-----------------------------------------------------
    /**
     * Get cache statistics
     *
     * @return array<string, mixed> Statistics array
     */
    public static function getStats(): array {
        return [
            'hits'        => self::$stats['hits'],
            'misses'      => self::$stats['misses'],
            'sets'        => self::$stats['sets'],
            'forgets'     => self::$stats['forgets'],
            'flushes'     => self::$stats['flushes'],
            'hit_rate'    => self::$stats['hits'] + self::$stats['misses'] > 0
                ? round((self::$stats['hits'] / (self::$stats['hits'] + self::$stats['misses'])) * 100, 2)
                : 0,
            'static_keys' => count(self::$staticCache),
        ];
    }

    /**
     * Reset cache statistics
     *
     * @return void
     */
    public static function resetStats(): void {
        self::$stats = [
            'hits'    => 0,
            'misses'  => 0,
            'sets'    => 0,
            'forgets' => 0,
            'flushes' => 0,
        ];

        LogHandler::cache('Cache statistics reset', []);
    }

    /**
     * Get all static cache keys
     *
     * @return array<string> Array of cache keys
     */
    public static function getStaticKeys(): array {
        return array_keys(self::$staticCache);
    }

    /**
     * Clear static cache (alias for flush with TYPE_STATIC)
     *
     * @return bool True on success, false otherwise
     */
    public static function clearStatic(): bool {
        return self::flush(self::TYPE_STATIC);
    }
}
