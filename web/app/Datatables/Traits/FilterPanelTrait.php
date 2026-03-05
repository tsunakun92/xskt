<?php

namespace App\Datatables\Traits;

use Exception;
use Illuminate\Support\Facades\Cache;

use App\Utils\CacheHandler;
use Modules\Logging\Utils\LogHandler;

/**
 * Filter Panel Trait
 *
 * Provides filter panel functionality for models.
 * This trait extracts all filter panel related functionality from BaseModel
 * to keep the BaseModel clean and modular.
 */
trait FilterPanelTrait {
    /**
     * Get filter panel columns array
     *
     * @return array
     */
    public static function getFilterPanelArray(): array {
        return (new static)->filterPanel ?? self::getFilterableArray();
    }

    /**
     * Get filter panel values for column dropdown filters with caching and optimization.
     *
     * @param  string  $column  The column name
     * @return array Array of distinct values for the column filter panel
     */
    public static function getFilterPanelColumnValues(string $column): array {
        $static   = new static;
        $cacheKey = 'filter_panel_values_' . $static->getTable() . '_' . $column;

        // Check cache first (cache for 1 hour)
        return Cache::remember($cacheKey, 3600, function () use ($static, $column) {
            // Check if column is in the filterPanel array
            if (in_array($column, $static->getFilterPanelArray())) {
                // Check if we have a mapping from display column to database column
                $filterColumnMapping = static::getFilterColumnMapping();

                if (isset($filterColumnMapping[$column])) {
                    // This is a mapped display column - handle both simple and advanced mappings
                    $mappingConfig = $filterColumnMapping[$column];

                    if (is_array($mappingConfig)) {
                        // Advanced mapping - use the getDistinctDisplayValuesForColumn with proper handling
                        return static::getDistinctDisplayValuesForColumn($column, $column);
                    } else {
                        // Simple mapping - $mappingConfig is a string (database column)
                        return static::getDistinctDisplayValuesForColumn($mappingConfig, $column);
                    }
                } else {
                    // This is a regular database column - get simple values with optimization
                    if (in_array($column, (new static)->getTableColumns())) {
                        try {
                            // Cache count query to avoid duplicate queries
                            $countCacheKey = 'table_count_' . $static->getTable();
                            $totalCount    = CacheHandler::remember($countCacheKey, function () {
                                return static::query()->count();
                            }, 60, CacheHandler::TYPE_STATIC); // Cache for 60 seconds in static cache

                            if ($totalCount > 5000) {
                                // For very large datasets, use sampling approach
                                $values = static::query()
                                    ->select($column)
                                    ->distinct()
                                    ->whereNotNull($column)
                                    ->limit(1000) // Limit to first 1000 distinct values
                                    ->pluck($column)
                                    ->map(function ($value) {
                                        if (is_null($value) || (is_string($value) && trim($value) === '')) {
                                            return '';
                                        }

                                        return (string) $value;
                                    })
                                    ->unique()
                                    ->sort()
                                    ->values()
                                    ->toArray();

                                return $values;
                            } else {
                                // For smaller datasets, get all values
                                $values = static::query()
                                    ->distinct()
                                    ->pluck($column)
                                    ->map(function ($value) {
                                        if (is_null($value) || (is_string($value) && trim($value) === '')) {
                                            return '';
                                        }

                                        return (string) $value;
                                    })
                                    ->unique()
                                    ->sort()
                                    ->values()
                                    ->toArray();

                                return $values;
                            }
                        } catch (Exception $e) {
                            LogHandler::error('Error getting filter panel values', [
                                'table'  => $static->getTable(),
                                'column' => $column,
                                'error'  => $e->getMessage(),
                            ]);

                            return [];
                        }
                    }
                }
            }

            return [];
        });
    }

    /**
     * Get filter column mapping - maps display column to database column for filtering
     * Override in child classes to provide custom mappings
     *
     * @return array ['display_column' => 'database_column' | ['type' => 'relationship', 'column' => 'column_name', 'relationship' => 'relationshipName', 'display_field' => 'field_name'] | ['type' => 'array', 'values' => ['key' => 'value']]]
     */
    public static function getFilterColumnMapping(): array {
        return [];
    }

    /**
     * Get distinct display values for a column with proper display names and optimization
     *
     * @param  string  $column  The column to get values for
     * @param  string  $displayColumn  The column to display (can be accessor)
     * @return array Array of distinct display values
     */
    public static function getDistinctDisplayValuesForColumn(string $column, ?string $displayColumn = null): array {
        $displayColumn = $displayColumn ?? $column;
        $static        = new static;
        $cacheKey      = 'distinct_display_values_' . $static->getTable() . '_' . $column . '_' . $displayColumn;

        return Cache::remember($cacheKey, 3600, function () use ($column, $displayColumn, $static) {
            // Check if we have advanced mapping configuration
            $filterColumnMapping = static::getFilterColumnMapping();
            if (isset($filterColumnMapping[$displayColumn]) && is_array($filterColumnMapping[$displayColumn])) {
                $mapping = $filterColumnMapping[$displayColumn];

                // Handle array-based values (like status, type)
                if (isset($mapping['type']) && $mapping['type'] === 'array') {
                    return array_values($mapping['values']);
                }

                // Handle relationship-based values
                if (isset($mapping['type']) && $mapping['type'] === 'relationship') {
                    return static::getDistinctRelationshipValues($mapping);
                }
            }

            // Cache count query to avoid duplicate queries
            $countCacheKey = 'table_count_' . $static->getTable();
            $totalCount    = CacheHandler::remember($countCacheKey, function () {
                return static::query()->count();
            }, 60, CacheHandler::TYPE_STATIC); // Cache for 60 seconds in static cache

            if ($totalCount > 5000) {
                // For large datasets, use chunked processing
                $results = [];
                self::query()
                    ->select([$column, 'id'])
                    ->distinct()
                    ->limit(5000) // Limit to prevent memory issues
                    ->chunk(1000, function ($records) use ($displayColumn, &$results) {
                        foreach ($records as $record) {
                            $value = $record->{$displayColumn};
                            if (is_null($value) || (is_string($value) && trim($value) === '')) {
                                $results[] = '';
                            } else {
                                $results[] = (string) $value;
                            }
                        }
                    });

                return array_values(array_unique(array_slice($results, 0, 1000))); // Limit final results
            } else {
                // For smaller datasets, use the original approach
                $results = self::query()
                    ->distinct()
                    ->get([$column, 'id']) // Get id to ensure we can access appended attributes
                    ->pluck($displayColumn)
                    ->map(function ($value) {
                        // Convert null, empty, or whitespace-only values to empty string
                        if (is_null($value) || (is_string($value) && trim($value) === '')) {
                            return '';
                        }

                        return (string) $value;
                    })
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();

                return $results;
            }
        });
    }

    /**
     * Get distinct values from a relationship configuration with optimization
     *
     * @param  array  $mapping  Relationship configuration array
     * @return array Array of distinct values
     */
    protected static function getDistinctRelationshipValues(array $mapping): array {
        $relationship = $mapping['relationship'];
        $displayField = $mapping['display_field'];
        $static       = new static;
        $cacheKey     = 'relationship_values_' . $static->getTable() . '_' . $relationship . '_' . $displayField;

        return Cache::remember($cacheKey, 3600, function () use ($relationship, $displayField, $static) {
            try {
                // Handle nested relationships (e.g., 'rUserDocument.rDocument')
                $relationshipParts = explode('.', $relationship);

                // Cache count query to avoid duplicate queries
                $countCacheKey = 'table_count_' . $static->getTable();
                $totalCount    = CacheHandler::remember($countCacheKey, function () {
                    return static::query()->count();
                }, 60, CacheHandler::TYPE_STATIC); // Cache for 60 seconds in static cache

                if ($totalCount > 10000) {
                    // For large datasets, use chunked processing
                    $displayValues = [];

                    self::query()
                        ->with($relationship)
                        ->limit(5000) // Limit records to process
                        ->chunk(500, function ($records) use ($relationshipParts, $displayField, &$displayValues) {
                            foreach ($records as $record) {
                                $relatedModel = $record;

                                // Navigate through nested relationships
                                foreach ($relationshipParts as $relationshipPart) {
                                    $relatedModel = $relatedModel?->{ $relationshipPart};
                                    if (!$relatedModel) {
                                        break;
                                    }
                                }

                                if ($relatedModel) {
                                    $value = $relatedModel->{$displayField};
                                    if (is_null($value) || (is_string($value) && trim($value) === '')) {
                                        $displayValues[] = '';
                                    } else {
                                        $displayValues[] = (string) $value;
                                    }
                                }
                            }
                        });

                    return array_values(array_unique($displayValues));
                } else {
                    // For smaller datasets, use the original approach
                    $allRecords = self::query()
                        ->with($relationship)
                        ->get();

                    $displayValues = [];
                    foreach ($allRecords as $record) {
                        $relatedModel = $record;

                        // Navigate through nested relationships
                        foreach ($relationshipParts as $relationshipPart) {
                            $relatedModel = $relatedModel?->{ $relationshipPart};
                            if (!$relatedModel) {
                                break;
                            }
                        }

                        if ($relatedModel) {
                            $value = $relatedModel->{$displayField};
                            // Convert null, empty, or whitespace-only values to a special empty label
                            if (is_null($value) || (is_string($value) && trim($value) === '')) {
                                $displayValues[] = '';
                            } else {
                                $displayValues[] = (string) $value;
                            }
                        }
                    }

                    return array_values(array_unique($displayValues));
                }
            } catch (Exception $e) {
                LogHandler::error('Error getting relationship values', [
                    'model'        => static::class,
                    'relationship' => $relationship,
                    'error'        => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Convert display values back to database values using enhanced mapping
     *
     * @param  string  $displayColumn  The display column name
     * @param  array  $displayValues  The display values to convert
     * @return array The corresponding database values
     */
    public static function convertDisplayValuesToDatabase(string $displayColumn, array $displayValues): array {
        $filterColumnMapping = static::getFilterColumnMapping();

        // Check if we have advanced mapping configuration
        if (isset($filterColumnMapping[$displayColumn]) && is_array($filterColumnMapping[$displayColumn])) {
            $mapping = $filterColumnMapping[$displayColumn];

            // Handle array-based values (like status, type)
            if (isset($mapping['type']) && $mapping['type'] === 'array') {
                return static::convertArrayDisplayValues($mapping['values'], $displayValues);
            }

            // Handle relationship-based values
            if (isset($mapping['type']) && $mapping['type'] === 'relationship') {
                return static::convertRelationshipDisplayValues($mapping, $displayValues);
            }
        }

        // Check for simple column mapping
        if (isset($filterColumnMapping[$displayColumn]) && is_string($filterColumnMapping[$displayColumn])) {
            $databaseColumn = $filterColumnMapping[$displayColumn];

            return static::convertSimpleDisplayValues($displayColumn, $databaseColumn, $displayValues);
        }

        // No mapping found, return as is (but with empty labels converted)
        return $displayValues;
    }

    /**
     * Convert array-based display values to database keys
     *
     * @param  array  $arrayValues  The array mapping (key => display_value)
     * @param  array  $displayValues  The display values to convert
     * @return array The corresponding database keys
     */
    protected static function convertArrayDisplayValues(array $arrayValues, array $displayValues): array {
        $databaseValues = [];
        $flippedArray   = array_flip($arrayValues); // Flip to get display_value => key

        foreach ($displayValues as $displayValue) {
            if (isset($flippedArray[$displayValue])) {
                $databaseValues[] = $flippedArray[$displayValue];
            }
        }

        return array_unique($databaseValues);
    }

    /**
     * Convert relationship display values to database foreign keys with optimization
     *
     * @param  array  $mapping  Relationship configuration
     * @param  array  $displayValues  The display values to convert
     * @return array The corresponding database foreign keys
     */
    protected static function convertRelationshipDisplayValues(array $mapping, array $displayValues): array {
        $relationship = $mapping['relationship'];
        $displayField = $mapping['display_field'];
        $column       = $mapping['column'];
        $static       = new static;

        // Create cache key based on display values
        $cacheKey = 'convert_relationship_' . $static->getTable() . '_' . $relationship . '_' . md5(serialize($displayValues));

        return Cache::remember($cacheKey, 1800, function () use ($relationship, $displayField, $column, $displayValues, $static) {
            try {
                // Handle nested relationships (e.g., 'rUserDocument.rDocument')
                $relationshipParts = explode('.', $relationship);

                // Cache count query to avoid duplicate queries
                $countCacheKey = 'table_count_' . $static->getTable();
                $totalCount    = CacheHandler::remember($countCacheKey, function () {
                    return static::query()->count();
                }, 60, CacheHandler::TYPE_STATIC); // Cache for 60 seconds in static cache

                if ($totalCount > 10000) {
                    // For large datasets, use chunked processing
                    $databaseValues = [];

                    self::query()
                        ->with($relationship)
                        ->limit(5000)
                        ->chunk(500, function ($records) use ($relationshipParts, $displayField, $column, $displayValues, &$databaseValues) {
                            foreach ($records as $record) {
                                $relatedModel = $record;

                                // Navigate through nested relationships
                                foreach ($relationshipParts as $relationshipPart) {
                                    $relatedModel = $relatedModel?->{ $relationshipPart};
                                    if (!$relatedModel) {
                                        break;
                                    }
                                }

                                if ($relatedModel) {
                                    $displayValue = (string) $relatedModel->{$displayField};
                                    if (in_array($displayValue, $displayValues)) {
                                        $databaseValues[] = $record->{$column};
                                    }
                                }
                            }
                        });

                    return array_unique($databaseValues);
                } else {
                    // For smaller datasets, use the original approach
                    $records = self::query()
                        ->with($relationship)
                        ->get();

                    $databaseValues = [];

                    foreach ($records as $record) {
                        $relatedModel = $record;

                        // Navigate through nested relationships
                        foreach ($relationshipParts as $relationshipPart) {
                            $relatedModel = $relatedModel?->{ $relationshipPart};
                            if (!$relatedModel) {
                                break;
                            }
                        }

                        if ($relatedModel) {
                            $displayValue = (string) $relatedModel->{$displayField};
                            if (in_array($displayValue, $displayValues)) {
                                $databaseValues[] = $record->{$column};
                            }
                        }
                    }

                    return array_unique($databaseValues);
                }
            } catch (Exception $e) {
                LogHandler::error('Error converting relationship display values', [
                    'model'        => static::class,
                    'relationship' => $relationship,
                    'error'        => $e->getMessage(),
                ]);

                return [];
            }
        });
    }

    /**
     * Convert simple display values to database values with caching and optimization
     *
     * @param  string  $displayColumn  The display column name
     * @param  string  $databaseColumn  The database column name
     * @param  array  $displayValues  The display values to convert
     * @return array The corresponding database values
     */
    protected static function convertSimpleDisplayValues(string $displayColumn, string $databaseColumn, array $displayValues): array {
        $static   = new static;
        $cacheKey = 'convert_simple_' . $static->getTable() . '_' . $displayColumn . '_' . $databaseColumn . '_' . md5(serialize($displayValues));

        return Cache::remember($cacheKey, 1800, function () use ($displayColumn, $databaseColumn, $displayValues, $static) {
            // Cache count query to avoid duplicate queries
            $countCacheKey = 'table_count_' . $static->getTable();
            $totalCount    = CacheHandler::remember($countCacheKey, function () {
                return self::query()->count();
            }, 60, CacheHandler::TYPE_STATIC); // Cache for 60 seconds in static cache

            // Get all records and create a mapping from display value to database value
            $mapping = [];

            if ($totalCount > 10000) {
                // For large datasets, use chunked processing
                self::query()
                    ->select([$databaseColumn, 'id'])
                    ->distinct()
                    ->limit(5000)
                    ->chunk(1000, function ($records) use ($displayColumn, $databaseColumn, &$mapping) {
                        foreach ($records as $record) {
                            $databaseValue = $record->{$databaseColumn};
                            $displayValue  = $record->{$displayColumn};

                            if (!is_null($databaseValue) && !is_null($displayValue)) {
                                $mapping[(string) $displayValue] = $databaseValue;
                            }
                        }
                    });
            } else {
                // For smaller datasets, use the original approach
                $records = self::query()
                    ->distinct()
                    ->get([$databaseColumn, 'id']);

                foreach ($records as $record) {
                    $databaseValue = $record->{$databaseColumn};
                    $displayValue  = $record->{$displayColumn};

                    if (!is_null($databaseValue) && !is_null($displayValue)) {
                        $mapping[(string) $displayValue] = $databaseValue;
                    }
                }
            }

            // Convert display values to database values
            $databaseFilterValues = [];
            foreach ($displayValues as $displayValue) {
                if (isset($mapping[(string) $displayValue])) {
                    $databaseFilterValues[] = $mapping[(string) $displayValue];
                } elseif ($displayValue === '') {
                    // Handle empty string case
                    $databaseFilterValues[] = '';
                }
            }

            return array_unique($databaseFilterValues);
        });
    }

    /**
     * Clear cached filter panel values
     */
    public static function clearFilterPanelCache(): void {
        $static = new static;
        $table  = $static->getTable();

        // Get filterable columns to clear specific cache keys
        $filterableColumns = static::getFilterPanelArray();

        foreach ($filterableColumns as $column) {
            // Clear filter panel values cache
            $cacheKey = 'filter_panel_values_' . $table . '_' . $column;
            Cache::forget($cacheKey);

            // Clear distinct display values cache
            $cacheKey = 'distinct_display_values_' . $table . '_' . $column . '_' . $column;
            Cache::forget($cacheKey);
        }

        // Clear relationship values cache (if any)
        $filterColumnMapping = static::getFilterColumnMapping();
        foreach ($filterColumnMapping as $column => $mapping) {
            if (is_array($mapping) && isset($mapping['type']) && $mapping['type'] === 'relationship') {
                $relationship = $mapping['relationship'];
                $displayField = $mapping['display_field'];
                $cacheKey     = 'relationship_values_' . $table . '_' . $relationship . '_' . $displayField;
                Cache::forget($cacheKey);
            }
        }

        // Note: Conversion caches use MD5 hashes in keys, making them hard to clear individually
        // For production, consider using cache tags with Redis:
        // Cache::tags(['filter_panel', $table])->flush();

        // Note: This clears specific known cache keys. For complete wildcard clearing,
        // use Redis with key patterns or implement cache tags
    }
}
