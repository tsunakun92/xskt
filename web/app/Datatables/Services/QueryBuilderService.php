<?php

namespace App\Datatables\Services;

use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use Throwable;

use Modules\Logging\Utils\LogHandler;

/**
 * Service for building and filtering database queries for datatables
 */
class QueryBuilderService {
    /**
     * Build base query from model
     */
    public function buildQuery(string $modelClass, array $filters, string $sortBy, string $sortDirection): Builder {
        if (!method_exists($modelClass, 'getAsDatatables')) {
            throw new InvalidArgumentException("Model {$modelClass} must implement getAsDatatables method");
        }

        return $modelClass::getAsDatatables($filters, $sortBy, $sortDirection);
    }

    /**
     * Apply column filters to query
     */
    public function applyColumnFilters(Builder $query, array $filters, string $modelClass): Builder {
        $filterColumnMapping = $this->getFilterColumnMapping($modelClass);

        foreach ($filters as $filterKey => $filterValues) {
            if (!$this->isColumnFilter($filterKey, $filterValues)) {
                continue;
            }

            $column = str_replace('_filter', '', $filterKey);

            if ($this->shouldSkipColumn($column, $filterColumnMapping, $modelClass)) {
                continue;
            }

            $this->applySingleColumnFilter($query, $column, $filterValues, $filterColumnMapping, $modelClass);
        }

        return $query;
    }

    /**
     * Get filter column mapping from model
     */
    protected function getFilterColumnMapping(string $modelClass): array {
        if (method_exists($modelClass, 'getFilterColumnMapping')) {
            return $modelClass::getFilterColumnMapping();
        }

        return [];
    }

    /**
     * Check if filter key represents column filter with values
     */
    protected function isColumnFilter(string $filterKey, $filterValues): bool {
        return str_ends_with($filterKey, '_filter') &&
        !empty($filterValues) &&
        is_array($filterValues) &&
        count($filterValues) > 0;
    }

    /**
     * Check if column should be skipped during filtering
     */
    protected function shouldSkipColumn(string $column, array $filterColumnMapping, string $modelClass): bool {
        // Skip if it's an appended attribute without mapping
        if ($this->isAppendedAttribute($column, $modelClass) && !isset($filterColumnMapping[$column])) {
            return true;
        }

        // Skip if not in filter panel array (if method exists)
        if (method_exists($modelClass, 'getFilterPanelArray')) {
            $filterPanelColumns = $modelClass::getFilterPanelArray();
            if (!in_array($column, $filterPanelColumns)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply single column filter to query
     */
    protected function applySingleColumnFilter(Builder $query, string $column, array $filterValues, array $filterColumnMapping, string $modelClass): void {
        $databaseColumn       = $this->resolveDatabaseColumn($column, $filterColumnMapping);
        $databaseFilterValues = $this->convertFilterValues($column, $filterValues, $filterColumnMapping, $modelClass);

        $this->addWhereClause($query, $databaseColumn, $databaseFilterValues);
    }

    /**
     * Resolve database column name from mapping
     */
    protected function resolveDatabaseColumn(string $column, array $filterColumnMapping): string {
        if (!isset($filterColumnMapping[$column])) {
            return $column;
        }

        $mappingConfig = $filterColumnMapping[$column];

        if (is_array($mappingConfig)) {
            return $this->extractDatabaseColumnFromMapping($mappingConfig, $column);
        }

        return $mappingConfig;
    }

    /**
     * Extract database column from mapping config
     */
    protected function extractDatabaseColumnFromMapping(array $mappingConfig, string $fallbackColumn): string {
        if (isset($mappingConfig['type']) && in_array($mappingConfig['type'], ['relationship', 'array'])) {
            return $mappingConfig['column'] ?? $fallbackColumn;
        }

        return $fallbackColumn;
    }

    /**
     * Convert display filter values to database values
     */
    protected function convertFilterValues(string $column, array $filterValues, array $filterColumnMapping, string $modelClass): array {
        if (isset($filterColumnMapping[$column]) && method_exists($modelClass, 'convertDisplayValuesToDatabase')) {
            return $modelClass::convertDisplayValuesToDatabase($column, $filterValues);
        }

        return $filterValues;
    }

    /**
     * Add WHERE clause for column filtering
     */
    protected function addWhereClause(Builder $query, string $databaseColumn, array $databaseFilterValues): void {
        $query->where(function (Builder $q) use ($databaseColumn, $databaseFilterValues) {
            if (in_array('', $databaseFilterValues)) {
                $q->whereIn($databaseColumn, $databaseFilterValues)
                    ->orWhereNull($databaseColumn)
                    ->orWhere($databaseColumn, '');
            } else {
                $q->whereIn($databaseColumn, $databaseFilterValues);
            }
        });
    }

    /**
     * Check if a column is an appended attribute
     */
    protected function isAppendedAttribute(string $column, string $modelClass): bool {
        try {
            $model   = new $modelClass;
            $appends = $model->getAppends();

            return in_array($column, $appends);
        } catch (Throwable $e) {
            LogHandler::warning("Unable to check appended attributes for {$modelClass}: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Get query statistics for debugging
     */
    public function getQueryStats(Builder $query): array {
        return [
            'sql'          => $query->toSql(),
            'bindings'     => $query->getBindings(),
            'wheres_count' => count($query->getQuery()->wheres),
            'joins_count'  => count($query->getQuery()->joins ?? []),
        ];
    }
}
