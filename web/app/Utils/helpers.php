<?php

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

use App\Utils\AddressHandle;
use App\Utils\CacheHandler;
use Modules\Admin\Models\Permission;

/**
 * ------------------------------------------------------------------------
 * String helper functions
 * ------------------------------------------------------------------------
 */
if (!function_exists('singular_case')) {
    /**
     * Return Singular Case.
     * Example: singular_case('categories') will return 'category'
     */
    function singular_case($text) {
        return Str::singular($text);
    }
}

if (!function_exists('plural_case')) {
    /**
     * Return Plural Case.
     * Example: plural_case('category') will return 'categories'
     */
    function plural_case($text) {
        return Str::plural($text);
    }
}

if (!function_exists('camel_case')) {
    /**
     * Return Camel Case.
     * Example: camel_case('product_category') will return 'productCategory'
     */
    function camel_case($text) {
        return Str::camel($text);
    }
}

if (!function_exists('studly_case')) {
    /**
     * Return Studly Case.
     * Example: studly_case('product_category') will return 'ProductCategory'
     */
    function studly_case($text) {
        return Str::studly($text);
    }
}

if (!function_exists('kebab_case')) {
    /**
     * Return Kebab Case.
     * Example: kebab_case('product_category') will return 'product-category'
     */
    function kebab_case($text) {
        // Replace underscores with hyphens
        return str_replace('_', '-', strtolower(preg_replace('/[A-Z]/', '-$0', lcfirst($text))));
    }
}

if (!function_exists('title_case')) {
    /**
     * Return Title Case.
     * Example: title_case('product_category') will return 'Product Category'
     */
    function title_case($text) {
        // Replace '-' and '_' with space
        $text = str_replace(['-', '_', '.'], ' ', $text);

        return Str::title($text);
    }
}

if (!function_exists('snake_case')) {
    /**
     * Return Snake Case.
     * Example: snake_case('categories') will return 'categories'
     * Example: snake_case('Product_Category') will return 'product_category'
     */
    function snake_case($text, $delimiter = '_') {
        return Str::snake($text, $delimiter);
    }
}

if (!function_exists('trim_text')) {
    /**
     * Return Trim Text.
     * Example: trim_text('Lorem ipsum dolor sit', 10) will return 'Lorem ipsu...'
     */
    function trim_text($text, $length = 100, $end = '...') {
        mb_internal_encoding('UTF-8');

        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length - strlen($end)) . $end;
        }

        return $text;
    }
}

if (!function_exists('trim_array_object')) {
    /**
     * Trim array/object data for logging purposes.
     * Recursively trims long strings and limits array items while preserving structure.
     *
     * @param  mixed  $data  Array or object to trim
     * @param  int  $maxStringLength  Maximum length for string values (default: 100)
     * @param  int  $maxArrayItems  Maximum number of items to keep in arrays (default: 10)
     * @param  int  $maxDepth  Maximum recursion depth (default: 5)
     * @param  int  $currentDepth  Current recursion depth (internal use)
     * @return mixed Trimmed array/object
     */
    function trim_array_object($data, $maxStringLength = 100, $maxArrayItems = 10, $maxDepth = 5, $currentDepth = 0) {
        if ($currentDepth >= $maxDepth) {
            return '... (max depth)';
        }

        // Convert object to array for unified processing
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (is_array($data)) {
            $result = [];
            $count  = 0;
            foreach ($data as $key => $value) {
                if ($count++ >= $maxArrayItems) {
                    $result['...'] = '... (' . (count($data) - $maxArrayItems) . ' more)';
                    break;
                }
                $result[$key] = trim_array_object($value, $maxStringLength, $maxArrayItems, $maxDepth, $currentDepth + 1);
            }

            return $result;
        }

        if (is_string($data) && mb_strlen($data) > $maxStringLength) {
            return mb_substr($data, 0, $maxStringLength) . '... (' . (mb_strlen($data) - $maxStringLength) . ' more)';
        }

        return $data;
    }
}

if (!function_exists('get_route_name')) {
    /**
     * Return Route Name.
     * Example: get_route_name('product-category.index') will return 'product-category'
     */
    function get_route_name($route) {
        if (count(explode('.', $route)) > 1) {
            return explode('.', $route)[0];
        }

        return $route;
    }
}

if (!function_exists('get_route_action')) {
    /**
     * Return Route Action.
     * Example: get_route_action('product-category.index') will return 'index'
     */
    function get_route_action($route) {
        if (count(explode('.', $route)) > 1) {
            return explode('.', $route)[1];
        }

        return $route;
    }
}

/**
 * ------------------------------------------------------------------------
 * Blade helper functions
 * ------------------------------------------------------------------------
 */
if (!function_exists('transOrDefault')) {
    /**
     * Translate a key if it exists, otherwise return the default value.
     * Example: @transOrDefault('key') will return the translated value of 'key' if it exists, otherwise 'key'
     * Example: @transOrDefault('key', 'default') will return the translated value of 'key' if it exists, otherwise 'default'
     */
    function transOrDefault($key, $default = null) {
        return Lang::has($key) ? __($key) : ($default ?? $key);
    }
}

if (!function_exists('can_access')) {
    /**
     * Check if authenticated user has permission.
     * Wrapper for User::canAccess() with super admin bypass.
     *
     * @param  string  $permission  Permission key (e.g., 'users.index')
     * @param  string  $platform  Platform (web, mobile, api). Default: 'web'
     * @return bool
     */
    function can_access(string $permission): bool {
        if (!auth()->check()) {
            return false;
        }

        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->canAccess($permission);
    }
}

if (!function_exists('get_permission_label')) {
    /**
     * Get permission label with priority: specific_permissions -> actions
     * Example: get_permission_label('changelog.index') will return 'View Changelog' if specific exists, otherwise 'View List'
     */
    function get_permission_label($permissionKey) {
        // Get specific permissions
        $specificPermissions = trans('admin::permission.specific_permissions');

        // Check if it's an array and contains our key
        if (is_array($specificPermissions) && isset($specificPermissions[$permissionKey])) {
            return $specificPermissions[$permissionKey];
        }

        // Fallback to action label
        $action = get_route_action($permissionKey);

        return trans("admin::permission.actions.{$action}");
    }
}

if (!function_exists('has_route')) {
    /**
     * Check if the authenticated user has the given route.
     *
     * @param  string  $route  The route string in the format route name (table-name.action)
     * @return bool
     */
    function has_route(string $route): bool {
        return Route::has($route);
    }
}

/**
 * ------------------------------------------------------------------------
 * Get Value
 * ------------------------------------------------------------------------
 */
if (!function_exists('get_value')) {
    /**
     * Get value in array or object
     *
     * @param  mixed  $data  Array or object
     * @param  string  $key  Key of array or object
     * @param  string  $default  Default value
     * @return mixed Value of key
     */
    function get_value($data, $key, $default = '') {
        if (is_array($data)) {
            return isset($data[$key]) ? $data[$key] : $default;
        }

        if (is_object($data)) {
            return isset($data->$key) ? $data->$key : $default;
        }

        return $default;
    }
}

/**
 * ------------------------------------------------------------------------
 * Address helper functions
 * ------------------------------------------------------------------------
 */
if (!function_exists('build_address_line')) {
    /**
     * Build full address line string for full-text search.
     *
     * @param  array|object  $source  Source data (e.g. Eloquent model or array) containing address fields
     * @return string|null Full address line or null when no data
     */
    function build_address_line($source): ?string {
        return AddressHandle::buildAddressLine($source);
    }
}

if (!function_exists('build_full_address')) {
    /**
     * Build human-readable full address string without postal code and coordinates.
     *
     * @param  array|object  $source  Source data (e.g. Eloquent model or array) containing address fields
     * @return string|null Full address or null when no data
     */
    function build_full_address($source): ?string {
        return AddressHandle::buildFullAddress($source);
    }
}

/**
 * ------------------------------------------------------------------------
 * Display helpers
 * ------------------------------------------------------------------------
 */
if (!function_exists('stringify_value')) {
    /**
     * Convert a mixed value into a safe, human-readable string for Blade output.
     * This prevents TypeError when Blade escapes arrays/objects via htmlspecialchars().
     *
     * @param  mixed  $value  Value to stringify
     * @param  string  $fallback  Fallback when value is null/empty/unrenderable
     * @return string
     */
    function stringify_value($value, string $fallback = ''): string {
        if ($value === null) {
            return $fallback;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        // Handle common framework types
        if ($value instanceof Illuminate\Support\Collection) {
            $value = $value->toArray();
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if (is_array($value)) {
            if ($value === []) {
                return $fallback;
            }

            // List array: join values
            if (function_exists('array_is_list') && array_is_list($value)) {
                $parts = array_map(function ($item) {
                    if ($item === null) {
                        return '';
                    }

                    if (is_scalar($item)) {
                        return (string) $item;
                    }

                    $json = json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    return $json === false ? '' : $json;
                }, $value);

                $parts = array_values(array_filter(array_map('trim', $parts), fn($v) => $v !== ''));

                return $parts ? implode(', ', $parts) : $fallback;
            }

            // Associative array: join key/value pairs
            $parts = [];
            foreach ($value as $k => $v) {
                $k = is_int($k) ? (string) $k : (string) $k;

                if ($v === null) {
                    $parts[] = "{$k}:";

                    continue;
                }

                if (is_scalar($v)) {
                    $parts[] = "{$k}: " . (string) $v;

                    continue;
                }

                $json    = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $parts[] = $json === false ? "{$k}:" : "{$k}: {$json}";
            }

            return $parts ? implode(', ', $parts) : $fallback;
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                try {
                    return (string) $value;
                } catch (Throwable $e) {
                    // Fall through to JSON encode
                }
            }

            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            return $json === false ? $fallback : $json;
        }

        return $fallback;
    }
}

/**
 * ------------------------------------------------------------------------
 * Number/Display formatting helpers
 * ------------------------------------------------------------------------
 */
if (!function_exists('fmt_number')) {
    /**
     * Format numeric values with thousand separators; return fallback for empty or '-'.
     *
     * @param  mixed  $value  Input value (string|int|float|null)
     * @param  string  $fallback  Value to show when input is empty or '-'
     * @param  int  $decimals  Number of decimals (default 0)
     * @return string
     */
    function fmt_number($value, string $fallback = '-', int $decimals = 0): string {
        if ($value === '-' || $value === null || $value === '') {
            return $fallback;
        }

        // Normalize strings: trim, remove thousand separators
        if (is_string($value)) {
            $normalized = str_replace([',', ' '], '', trim($value));
        } else {
            $normalized = $value;
        }

        if (is_numeric($normalized)) {
            return number_format((float) $normalized, $decimals);
        }

        // For any non-numeric or malformed input, return fallback
        return $fallback;
    }
}

/**
 * Convert array to string
 *
 * @param  array  $array
 * @param  string  $separator
 * @return string
 */
if (!function_exists('array_to_string')) {
    function array_to_string($array, $separator = ',') {
        if (empty($array)) {
            return '';
        }

        return implode($separator, $array);
    }
}

/**
 * Convert array to string
 *
 * @param  string  $string
 * @param  string  $separator
 * @return array
 */
if (!function_exists('string_to_array')) {
    function string_to_array($string, $separator = ',') {
        return explode($separator, $string);
    }
}

/**
 * ------------------------------------------------------------------------
 * Cache helper functions
 * ------------------------------------------------------------------------
 */
if (!function_exists('cache_get')) {
    /**
     * Get value from cache using CacheHandler
     *
     * @param  string  $key  Cache key
     * @param  mixed  $default  Default value if key not found
     * @param  string  $type  Cache type (static or persistent)
     * @return mixed Cached value or default
     */
    function cache_get(string $key, $default = null, string $type = CacheHandler::TYPE_STATIC) {
        return CacheHandler::get($key, $default, $type);
    }
}

if (!function_exists('cache_set')) {
    /**
     * Set value in cache using CacheHandler
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to cache
     * @param  int|null  $ttl  Time to live in seconds (only for persistent cache)
     * @param  string  $type  Cache type (static or persistent)
     * @return bool True on success, false otherwise
     */
    function cache_set(string $key, $value, ?int $ttl = null, string $type = CacheHandler::TYPE_STATIC): bool {
        return CacheHandler::set($key, $value, $ttl, $type);
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Forget cache key using CacheHandler
     *
     * @param  string  $key  Cache key
     * @param  string  $type  Cache type (static or persistent)
     * @return bool True on success, false otherwise
     */
    function cache_forget(string $key, string $type = CacheHandler::TYPE_STATIC): bool {
        return CacheHandler::forget($key, $type);
    }
}

if (!function_exists('cache_remember')) {
    /**
     * Get value from cache or execute callback if not found using CacheHandler
     *
     * @param  string  $key  Cache key
     * @param  callable  $callback  Callback to execute if cache miss
     * @param  int|null  $ttl  Time to live in seconds (only for persistent cache)
     * @param  string  $type  Cache type (static or persistent)
     * @return mixed Cached value or callback result
     */
    function cache_remember(string $key, callable $callback, ?int $ttl = null, string $type = CacheHandler::TYPE_STATIC) {
        return CacheHandler::remember($key, $callback, $ttl, $type);
    }
}

/**
 * ------------------------------------------------------------------------
 * API Response helper functions
 * ------------------------------------------------------------------------
 */
if (!function_exists('apiResponseError')) {
    /**
     * Returns error response with given message and data.
     *
     * @param  string  $message  The error message.
     * @param  array  $data  The additional data to include.
     * @return array The error response.
     *
     * @example apiResponseError('Error', ['data' => 'data']);
     */
    function apiResponseError(string $message, array $data = []): array {
        return array_merge([
            'status'  => App\Utils\DomainConst::API_RESPONSE_STATUS_FAILED,
            'message' => $message,
        ], $data);
    }
}

if (!function_exists('apiResponseSuccess')) {
    /**
     * Returns success response with given message and data.
     *
     * @param  string  $message  The success message.
     * @param  array  $data  The additional data to include.
     * @return array The success response.
     *
     * @example apiResponseSuccess('Success', ['data' => 'data']);
     */
    function apiResponseSuccess(string $message, array $data = []): array {
        return array_merge([
            'status'  => App\Utils\DomainConst::API_RESPONSE_STATUS_SUCCESS,
            'message' => $message,
        ], $data);
    }
}

if (!function_exists('getValidationErrorMessage')) {
    /**
     * Get validation error message in English regardless of current locale.
     *
     * @param  Illuminate\Contracts\Validation\Validator  $validator  The validator instance.
     * @return string The first validation error message in English.
     */
    function getValidationErrorMessage(Illuminate\Contracts\Validation\Validator $validator): string {
        // Save current locale
        $currentLocale = app()->getLocale();

        // Temporarily set locale to English to get English error messages
        app()->setLocale('en');

        // Get the first error message
        $errorMessage = $validator->errors()->first();

        // Restore original locale
        app()->setLocale($currentLocale);

        return $errorMessage;
    }
}

/**
 * ------------------------------------------------------------------------
 * JSON Display helper functions
 * ------------------------------------------------------------------------
 */
if (!function_exists('is_json_string')) {
    /**
     * Check if a string is valid JSON.
     *
     * @param  mixed  $value  The value to check
     * @return bool True if the value is a valid JSON string
     */
    function is_json_string($value): bool {
        if (!is_string($value) || empty($value)) {
            return false;
        }

        // Quick check: JSON strings usually start with { or [
        $trimmed = trim($value);
        if (!in_array($trimmed[0] ?? '', ['{', '['])) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}

if (!function_exists('parse_json_display_data')) {
    /**
     * Parse JSON value and detect GraphQL query for display purposes.
     *
     * @param  string  $value  The JSON string to parse
     * @return array{jsonData: string|null, isJson: bool, hasGraphQL: bool, graphqlQuery: string|null, otherData: string|null}
     */
    function parse_json_display_data(string $value): array {
        $jsonData     = null;
        $isJson       = false;
        $hasGraphQL   = false;
        $graphqlQuery = null;
        $otherData    = null;

        if (empty($value)) {
            return [
                'jsonData'     => null,
                'isJson'       => false,
                'hasGraphQL'   => false,
                'graphqlQuery' => null,
                'otherData'    => null,
            ];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $isJson = true;

            // Check if contains GraphQL query
            if (isset($decoded['query']) && is_string($decoded['query'])) {
                $hasGraphQL = true;
                // Format GraphQL query (replace \n with actual newlines and clean up)
                $graphqlQuery = str_replace(['\\n', '\\t'], ["\n", "\t"], $decoded['query']);
                $graphqlQuery = preg_replace('/\n\s*\n/', "\n", $graphqlQuery); // Remove extra blank lines

                // Get other data without query field
                unset($decoded['query']);
                $otherData = !empty($decoded)
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null;
            } else {
                // Regular JSON without GraphQL
                $jsonData = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return [
            'jsonData'     => $jsonData,
            'isJson'       => $isJson,
            'hasGraphQL'   => $hasGraphQL,
            'graphqlQuery' => $graphqlQuery,
            'otherData'    => $otherData,
        ];
    }
}
