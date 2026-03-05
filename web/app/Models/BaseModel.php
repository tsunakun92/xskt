<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

use App\Datatables\Models\DatatableModel;
use App\Models\Traits\ModelCacheTrait;
use App\Utils\CacheHandler;
use App\Utils\DomainConst;
use Modules\Admin\Models\User;
use Modules\Logging\Utils\LogHandler;

/**
 * Base model for all application models.
 * Provides datatables support, caching via ModelCacheTrait, auto-logging,
 * status management, and field configuration methods.
 *
 * @mixin \Eloquent
 */
class BaseModel extends Model {
    use DatatableModel;
    use ModelCacheTrait;

    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------

    /**
     * Status: Inactive.
     *
     * @var int
     */
    public const STATUS_INACTIVE = 0;

    /**
     * Status: Active.
     *
     * @var int
     */
    public const STATUS_ACTIVE = 1;

    /**
     * Status: Register Request.
     *
     * @var int
     */
    public const STATUS_REGISTER_REQUEST = 2;

    /**
     * Field type: Form.
     *
     * @var string
     */
    public const TYPE_FIELD_FORM = 'form';

    /**
     * Field type: Filter.
     *
     * @var string
     */
    public const TYPE_FIELD_FILTER = 'filter';

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------

    //-----------------------------------------------------
    // Override methods
    //-----------------------------------------------------
    /**
     * The "booted" method of the model.
     * Sets up model event listeners for auto-logging and created_by assignment.
     *
     * @return void
     */
    protected static function boot(): void {
        parent::boot();

        // Auto set created_by and logging when creating
        static::creating(function ($model) {
            // Auto set created_by
            if (auth()->check() && in_array('created_by', $model->getTableColumns())) {
                $model->created_by = auth()->id();
            }
            // Logging
            LogHandler::info('Creating new ' . str_replace('_', ' ', $model->getTable()));
        });

        // Logging for created
        static::created(function ($model) {
            $keyName = $model->getKeyName();
            $logData = [];
            if (is_string($keyName)) {
                $logData[$keyName] = $model->id;
            }
            LogHandler::info(ucfirst(str_replace('_', ' ', $model->getTable())) . ' created successfully', $logData);
        });

        // Logging for updating
        static::updating(function ($model) {
            $dirty = $model->getDirty();
            if (!empty($dirty)) {
                $keyName = $model->getKeyName();
                $logData = [];
                if (is_string($keyName)) {
                    $logData[$keyName] = $model->id;
                }
                $logData['dirty_fields'] = array_keys($dirty);
                LogHandler::info('Updating ' . str_replace('_', ' ', $model->getTable()), $logData);
            }
        });

        // Logging for updated
        static::updated(function ($model) {
            $keyName = $model->getKeyName();
            $logData = [];
            if (is_string($keyName)) {
                $logData[$keyName] = $model->id;
            }
            $logData['changed_fields'] = array_keys($model->getDirty());
            LogHandler::info(ucfirst(str_replace('_', ' ', $model->getTable())) . ' updated successfully', $logData);
        });

        // Logging for deleting
        static::deleting(function ($model) {
            $keyName = $model->getKeyName();
            $logData = [];
            if (is_string($keyName)) {
                $logData[$keyName] = $model->id;
            }
            LogHandler::warning('Deleting ' . str_replace('_', ' ', $model->getTable()), $logData);
        });

        // Logging for deleted
        static::deleted(function ($model) {
            $keyName = $model->getKeyName();
            $logData = [];
            if (is_string($keyName)) {
                $logData[$keyName] = $model->id;
            }
            LogHandler::info(ucfirst(str_replace('_', ' ', $model->getTable())) . ' deleted successfully', $logData);
        });
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------
    /**
     * Get the user that created the record.
     *
     * @return BelongsTo
     */
    public function rCreatedBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    //-----------------------------------------------------
    // Utility methods
    //-----------------------------------------------------
    /**
     * Get relationship value with eager loading.
     * Loads the relationship if not already loaded and returns the specified field value.
     *
     * @param  string  $relationName
     * @param  string  $fieldName
     * @param  int|null  $foreignKey
     * @return string
     */
    protected function getRelationshipValue(string $relationName, string $fieldName = 'name', ?int $foreignKey = null): string {
        if ($foreignKey === null) {
            $keyName    = str_replace('r', '', $relationName);
            $keyName    = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $keyName)) . '_id';
            $foreignKey = $this->getAttribute($keyName) ?? 0;
        }

        if (!$foreignKey) {
            return '';
        }

        try {
            $this->loadMissing($relationName);

            $related = $this->getRelation($relationName);
            if ($related && isset($related->{$fieldName})) {
                return $related->{$fieldName};
            }
        } catch (Exception $e) {
            return '';
        }

        return '';
    }

    /**
     * Get route parameters for the model.
     * Returns array for composite keys or single value for simple key.
     *
     * @return array|int|string
     */
    public function getRouteParams(): array|int|string {
        $keyName = $this->getKeyName();

        if (is_array($keyName)) {
            $params = [];
            foreach ($keyName as $key) {
                $params[$key] = $this->getAttribute($key);
            }

            return $params;
        }

        return $this->getKey();
    }

    /**
     * Scope to filter models with active status.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Full-text search on specified column
     *
     * @param  Builder  $query
     * @param  string  $column  Column name to search
     * @param  string|null  $value  Search value
     * @return Builder
     */
    public function scopeSearchFullText(Builder $query, string $column, ?string $value): Builder {
        if ($value) {
            // Validate column exists in table to prevent SQL injection
            $instance     = $query->getModel();
            $tableColumns = $instance->getTableColumns();

            if (in_array($column, $tableColumns)) {
                $tableName = $instance->getTable();
                $query->whereRaw("MATCH(`{$tableName}`.`{$column}`) AGAINST(? IN BOOLEAN MODE)", [$value]);
            }
        }

        return $query;
    }

    /**
     * Scope: Filter models by address using full-text search on address_line column.
     * Safely no-ops when value is empty or model does not have address_line column.
     *
     * @param  Builder  $query
     * @param  string|null  $address
     * @return Builder
     */
    public function scopeSearchByAddress(Builder $query, ?string $address): Builder {
        if ($address === null || trim($address) === '') {
            return $query;
        }

        $instance     = $query->getModel();
        $tableColumns = $instance->getTableColumns();

        if (!in_array('address_line', $tableColumns, true)) {
            return $query;
        }

        return $instance->scopeSearchFullText($query, 'address_line', $address);
    }

    /**
     * Scope filter for dynamic filtering based on model attributes.
     * Supports custom filter methods, LIKE searches, and exact/in array matches.
     *
     * @param  Builder  $query
     * @param  array  $params  Filter parameters
     * @param  array|null  $filterableOverride  Override filterable array
     * @return Builder
     */
    public function scopeFilter(Builder $query, array $params, ?array $filterableOverride = null): Builder {
        $filterable = $filterableOverride ?? $this->filterable ?? [];
        $filterLike = $this->filterLike ?? [];

        if (empty($filterable) || !is_array($filterable)) {
            return $query;
        }

        $params = collect($params)
            ->only($filterable)
            ->filter(fn($value) => !is_null($value) && $value !== '')
            ->toArray();

        foreach ($params as $field => $value) {
            $method = 'filter' . Str::studly($field);

            if (method_exists($this, $method)) {
                // Priority custom method
                $query = call_user_func([$this, $method], $query, $value);
            } elseif (in_array($field, $filterLike)) {
                // LIKE search for specified fields
                $query->where($this->getTable() . '.' . $field, 'like', '%' . $value . '%');
            } else {
                // Default is exact match
                if (is_array($value)) {
                    $query->whereIn($this->getTable() . '.' . $field, $value);
                } else {
                    $query->where($this->getTable() . '.' . $field, $value);
                }
            }
        }

        return $query;
    }

    /**
     * Get status name from status array.
     *
     * @return string
     */
    public function getStatusName(): string {
        // If not has field status return empty string
        if (!in_array('status', $this->getTableColumns())) {
            return '';
        }

        $statusList = static::getStatusArray();

        return $statusList[$this->status] ?? '';
    }

    /**
     * Get table columns.
     * Uses Laravel 12's once() helper for request-level caching.
     *
     * @return array
     */
    public function getTableColumns(): array {
        $cacheKey = "table_columns:{$this->getTable()}";

        return once(function () use ($cacheKey) {
            return CacheHandler::remember($cacheKey, function () {
                return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
            }, null, CacheHandler::TYPE_STATIC);
        });
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------
    /**
     * Get datatables table name.
     *
     * @return string
     */
    public static function getDatatableTableName(): string {
        return (new static)->getTable();
    }

    /**
     * Get datatable table columns with raw database column names.
     * This base implementation is translation-agnostic and simply
     * returns the column keys themselves as labels.
     *
     * @param  bool  $addActionColumn  Whether to include the 'action' column or not
     * @param  string|null  $keyLang  Optional custom key for translation
     * @return array
     */
    public static function getBaseDatatableTableColumns(bool $addActionColumn = true, ?string $keyLang = null): array {
        $instance = new static;
        $columns  = $instance->datatableColumns ?? [];
        if (!method_exists($instance, 'useDatatables') || !$instance->useDatatables) {
            return $columns;
        }

        $columnLabels = [];

        if (empty($columns)) {
            return [];
        }

        if (!$addActionColumn && in_array('action', $columns)) {
            unset($columns[array_search('action', $columns)]);
        }

        foreach ($columns as $column) {
            // Base model: use raw column name as label
            $columnLabels[$column] = $column;
        }

        // Add the common 'action' column if needed
        if ($addActionColumn) {
            $columnLabels['action'] = 'action';
        }

        return $columnLabels;
    }

    /**
     * Get fillable array.
     *
     * @return array
     */
    public static function getFillableArray(): array {
        return (new static)->fillable ?? [];
    }

    /**
     * Get datatable columns.
     *
     * @return array
     */
    public static function getDatatableColumns(): array {
        $instance = new static;

        return $instance->datatableColumns ?? [];
    }

    /**
     * Get datatable group columns.
     *
     * @return array
     */
    public static function getDatatableGroupColumns(): array {
        $instance = new static;

        return $instance->datatableGroupColumns ?? [];
    }

    /**
     * Get filter panel columns.
     *
     * @return array
     */
    public static function getFilterPanel(): array {
        $instance = new static;

        return $instance->filterPanel ?? [];
    }

    /**
     * Get filterable array.
     *
     * @return array
     */
    public static function getFilterableArray(): array {
        return (new static)->filterable ?? [];
    }

    /**
     * Get status array with translations.
     *
     * @param  bool  $addPleaseSelect
     * @return array
     */
    public static function getStatusArray(bool $addPleaseSelect = true): array {
        $status = [
            self::STATUS_INACTIVE => __('admin::app.inactive'),
            self::STATUS_ACTIVE   => __('admin::app.active'),
        ];

        if ($addPleaseSelect) {
            $status = [DomainConst::VALUE_EMPTY => __('admin::crud.please_select')] + $status;
        }

        return $status;
    }

    /**
     * Get fields configuration for form or filter.
     * Base implementation is translation-agnostic and uses
     * raw field names for labels and placeholders.
     *
     * @param  string  $type  Type of configuration: 'form' or 'filter'
     * @param  string  $routeName  Route name for translation
     * @param  string|null  $action  Action name for form (optional)
     * @return array<string, array<string, mixed>>
     */
    public static function getFields(string $type, string $routeName, ?string $action = null): array {
        $fields            = [];
        $attributesSource  = $type === self::TYPE_FIELD_FORM ? static::getFillableArray() : static::getFilterableArray();
        $defaultAttributes = [
            'type'     => 'text',
            'value'    => '',
            'required' => DomainConst::VALUE_FALSE,
            'readonly' => DomainConst::VALUE_FALSE,
            'hidden'   => DomainConst::VALUE_FALSE,
            'disabled' => DomainConst::VALUE_FALSE,
            'options'  => [],
            'class'    => '',
        ];

        // Check if the action is "create"
        $isCreateAction = $action === DomainConst::ACTION_CREATE;

        foreach ($attributesSource as $field) {
            // Base attributes for each field
            $fields[$field] = array_merge($defaultAttributes, [
                'label'       => $field,
                'placeholder' => $field,
            ]);

            // Specific logic for form fields
            if ($type === self::TYPE_FIELD_FORM && $field === 'status') {
                $fields[$field]['type']    = 'select';
                $fields[$field]['options'] = static::getStatusArray(true);
                $fields[$field]['value']   = self::STATUS_ACTIVE;
                $fields[$field]['hidden']  = $isCreateAction ? DomainConst::VALUE_TRUE : DomainConst::VALUE_FALSE;
            }
        }

        return $fields;
    }

    /**
     * Get form fields configuration.
     *
     * @param  string  $routeName
     * @param  string|null  $action
     * @return array<string, array<string, mixed>>
     */
    public static function getFormFields(string $routeName, ?string $action = null): array {
        return static::getFields(self::TYPE_FIELD_FORM, $routeName, $action);
    }

    /**
     * Get filter fields configuration.
     *
     * @param  string  $routeName
     * @return array<string, array<string, mixed>>
     */
    public static function getFilterFields(string $routeName): array {
        return static::getFields(self::TYPE_FIELD_FILTER, $routeName);
    }

    /**
     * Get as dropdown array for select options.
     *
     * @param  bool  $isAddPleaseSelect
     * @param  string  $key
     * @param  string  $value
     * @return array
     */
    public static function getAsDropdown(bool $isAddPleaseSelect = true, string $key = 'name', string $value = 'id'): array {
        // Get all items active
        $query = static::active();

        // Get dropdown
        $dropdown = $query->pluck($key, $value);

        // Add please select
        if ($isAddPleaseSelect) {
            $dropdown = $dropdown->prepend(__('admin::crud.please_select'), DomainConst::VALUE_ZERO);
        }

        // Return array
        return $dropdown->toArray();
    }

    /**
     * Get model by ID or throw exception if not found.
     *
     * @param  int  $id
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     *
     * @return static
     */
    public static function getById(int $id): static {
        return self::findOrFail($id);
    }
}
