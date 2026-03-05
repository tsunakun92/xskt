<?php

namespace App\Datatables\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;

use App\Datatables\Traits\FilterPanelTrait;

/**
 * Datatables Model Trait
 *
 * Provides ONLY datatables-specific functionality to Eloquent models.
 * Contains methods that are specifically for datatables display and interaction.
 *
 * General utilities (forms, filtering, dropdowns, export/import) remain in BaseModel.
 */
trait DatatableModel {
    use FilterPanelTrait;

    /**
     * Use datatables
     */
    protected $useDatatables = true;

    /**
     * Boot the trait - set up cache clearing
     */
    protected static function bootDatatableModel() {
        // Clear cache on model changes for all models using this trait
        static::saved(function ($model) {
            static::clearFilterPanelCache();
        });

        static::deleted(function ($model) {
            static::clearFilterPanelCache();
        });
    }

    /**
     * Show filter panel
     */
    protected $showFilterPanel = true;

    /**
     * Show filter form
     */
    protected $showFilterForm = true;

    /**
     * Check is use datatables
     */
    public static function useDatatables(): bool {
        $instance = new static;

        return $instance->useDatatables ?? true;
    }

    /**
     * Check if filter panel should be shown
     */
    public static function showFilterPanel(): bool {
        $instance = new static;

        return $instance->showFilterPanel ?? true;
    }

    /**
     * Check if filter form should be shown
     */
    public static function showFilterForm(): bool {
        $instance = new static;

        return $instance->showFilterForm ?? true;
    }

    /**
     * Get datatable group columns for collapsible functionality.
     * This is SPECIFICALLY for datatables collapsible groups.
     *
     * @return array
     */
    public static function getDatatableTableGroupColumns(): array {
        return (new static)->datatableGroupColumns ?? [];
    }

    /**
     * Get data for DataTables with filtering, sorting, and pagination.
     * This method is SPECIFICALLY for datatables data retrieval with performance optimizations.
     *
     * @param  array  $filters  Filter parameters from DataTables
     * @param  string  $sortBy  Column to sort by
     * @param  string  $sortDirection  Sort direction (asc/desc)
     * @return Builder
     */
    public static function getAsDatatables(array $filters = [], string $sortBy = 'id', string $sortDirection = 'asc'): Builder {
        $instance = new static;
        $query    = $instance->query();

        // Apply optimized select for datatables
        $query = static::applyDatatablesOptimization($query, $instance);

        // Apply eager loading for relationships
        if (!empty($instance->indexWith)) {
            $query->with($instance->indexWith);
        }

        // Apply base filters using the existing filter scope
        $query->filter($filters);

        // Apply sorting
        if (!empty($sortBy)) {
            // Check if this is an appended attribute by creating a model instance
            $modelInstance = new static;
            $appends       = $modelInstance->getAppends();

            // Only apply database-level sorting for non-appended attributes
            if (!in_array($sortBy, $appends)) {
                // Check for SQL sorting expressions
                $sqlSortingExpressions = static::getSqlSortingExpressions();
                if (isset($sqlSortingExpressions[$sortBy])) {
                    $query->orderByRaw($sqlSortingExpressions[$sortBy] . ' ' . $sortDirection);
                } else {
                    $query->orderBy($sortBy, $sortDirection);
                }
            }
        }

        return $query;
    }

    /**
     * Get SQL sorting expressions for virtual columns
     */
    public static function getSqlSortingExpressions(): array {
        return [];
    }

    /**
     * Get distinct display values for a column with proper display names
     * This is PRIMARILY for datatables filter panel values.
     *
     * @param  string  $column  The column to get values for
     * @param  string  $displayColumn  The column to display (can be accessor)
     * @return array Array of distinct display values
     */
    public static function getDistinctDisplayValuesForColumn(string $column, ?string $displayColumn = null): array {
        $displayColumn = $displayColumn ?? $column;

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

        // Get distinct records with the display column
        $results = self::query()
            ->distinct()
            ->get([$column, 'id'])
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

    /**
     * Get filter column mapping - override in child classes
     */
    public static function getFilterColumnMapping(): array {
        return [];
    }

    /**
     * Apply datatables optimization - select only necessary columns
     */
    protected static function applyDatatablesOptimization($query, $instance) {
        // Get datatable columns if defined
        $datatableColumns = $instance->datatableColumns ?? null;

        if (is_array($datatableColumns) && !empty($datatableColumns)) {
            // Skip optimization if model has appended attributes to avoid breaking relationships
            $appends = $instance->getAppends();
            if (!empty($appends)) {
                return $query;
            }

            // Automatically select only the columns needed for datatables
            // Remove 'action' column as it's virtual
            $selectColumns = array_filter($datatableColumns, function ($column) {
                return $column !== 'action';
            });

            // Ensure we always have id and timestamps
            $essentialColumns = ['id', 'created_at', 'updated_at'];

            // Add foreign key columns for relationships defined in indexWith
            $relationshipColumns = static::getRelationshipForeignKeys($instance);
            $selectColumns       = array_unique(array_merge($selectColumns, $essentialColumns, $relationshipColumns));

            // Only apply select if we have actual database columns
            $tableColumns = $instance->getTableColumns();
            $validColumns = array_intersect($selectColumns, $tableColumns);

            if (!empty($validColumns)) {
                $query->select($validColumns);
            }
        }

        return $query;
    }

    /**
     * Get foreign key columns for relationships defined in indexWith
     */
    protected static function getRelationshipForeignKeys($instance): array {
        $foreignKeys = [];
        $indexWith   = $instance->indexWith ?? [];

        foreach ($indexWith as $relation) {
            // Skip nested relationships for now (e.g., 'rUser.rCompany')
            if (strpos($relation, '.') !== false) {
                continue;
            }

            // Try to get foreign key from relationship method
            try {
                if (method_exists($instance, $relation)) {
                    $relationInstance = $instance->$relation();
                    if (method_exists($relationInstance, 'getForeignKeyName')) {
                        $foreignKeys[] = $relationInstance->getForeignKeyName();
                    } elseif (method_exists($relationInstance, 'getOwnerKeyName')) {
                        // For belongsTo relationships
                        $foreignKeys[] = $relationInstance->getForeignKeyName();
                    }
                }
            } catch (Exception $e) {
                // Skip if relationship can't be determined
                continue;
            }
        }

        return $foreignKeys;
    }
}
