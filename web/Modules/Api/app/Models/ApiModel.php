<?php

namespace Modules\Api\Models;

use App\Models\BaseModel;
use App\Utils\DomainConst;

/**
 * Base model for Api module.
 * Extends BaseModel and overrides translation methods for api::crud namespace.
 */
class ApiModel extends BaseModel {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------

    //-----------------------------------------------------
    // Override methods
    //-----------------------------------------------------

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------

    //-----------------------------------------------------
    // Utility methods
    //-----------------------------------------------------

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------
    /**
     * Get datatable table columns with Api translation namespace.
     *
     * @param  bool  $addActionColumn  Whether to include the 'action' column or not
     * @param  string|null  $keyLang  Optional custom key for translation
     * @return array<string, string>
     */
    public static function getBaseDatatableTableColumns(bool $addActionColumn = true, ?string $keyLang = null): array {
        $instance = new static;
        $columns  = $instance->datatableColumns ?? [];
        if (!method_exists($instance, 'useDatatables') || !$instance->useDatatables) {
            return $columns;
        }

        $tableName    = kebab_case($instance->getTable());
        $columnLabels = [];

        if (empty($columns)) {
            return [];
        }

        if (!$addActionColumn && in_array('action', $columns)) {
            unset($columns[array_search('action', $columns)]);
        }

        foreach ($columns as $column) {
            // Automatically generates the translation key for api module
            $columnLabels[$column] = __('api::crud.' . ($keyLang ?? $tableName) . '.' . $column);
        }

        // Add the common 'action' column if needed
        if ($addActionColumn) {
            $columnLabels['action'] = __('api::crud.action');
        }

        return $columnLabels;
    }

    /**
     * Get fields configuration for form or filter for Api module.
     *
     * @param  string  $type  Type of configuration: 'form' or 'filter'
     * @param  string  $routeName  Route name for translation
     * @param  string|null  $action  Action name for form (optional)
     * @return array<string, array<string, mixed>>
     */
    protected static function getApiFields(string $type, string $routeName, ?string $action = null): array {
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
                'label'       => __('api::crud.' . $routeName . '.' . ($type === self::TYPE_FIELD_FORM ? $field : 'filter.' . $field)),
                'placeholder' => __('api::crud.' . $routeName . '.' . ($type === self::TYPE_FIELD_FORM ? $field : 'filter.' . $field)),
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
     * Get form fields for Api module.
     *
     * @param  string  $routeName
     * @param  string|null  $action
     * @return array<string, array<string, mixed>>
     */
    public static function getFormFields(string $routeName, ?string $action = null): array {
        return self::getApiFields(self::TYPE_FIELD_FORM, $routeName, $action);
    }

    /**
     * Get filter fields for Api module.
     *
     * @param  string  $routeName
     * @return array<string, array<string, mixed>>
     */
    public static function getFilterFields(string $routeName): array {
        return self::getApiFields(self::TYPE_FIELD_FILTER, $routeName);
    }
}
