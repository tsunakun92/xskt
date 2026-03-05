<?php

namespace Modules\Admin\Models;

use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Modules\Logging\Utils\LogHandler;

/**
 * Class Permission
 *
 * @property int $id
 * @property string $name
 * @property string $key
 * @property string $group
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|Role[] $rRoles
 */
class Permission extends AdminModel {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------
    const MODULE_ADMIN = 'admin';

    const MODULE_HR    = 'hr';

    const MODULE_CRM   = 'crm';

    const MODULE_API   = 'api';

    const MODULE_MOBILES = 'mobiles';

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------
    /** Fillable array */
    protected $fillable = [
        'name',
        'key',
        'group',
        'module',
        'status',
    ];

    /**
     * Datatable columns array
     * NOTE: index on first column and action on last column
     */
    protected $datatableColumns = [
        'id',
        'name',
        'key',
        'group',
        'module',
        'status',
        'action',
    ];

    /**
     * Filterable columns.
     *
     * @var array<int, string>
     */
    protected $filterable = [
        'name',
        'key',
        'group',
        'module',
        'status',
    ];

    /**
     * The attributes that should be filtered with 'LIKE' comparison.
     *
     * @var array<int, string>
     */
    protected $filterLike = ['name', 'key', 'group', 'module'];

    /**
     * Filter panel
     *
     * @var array<int, string>
     */
    protected $filterPanel = [
        'id',
        'name',
        'key',
        'group',
        'module',
        'status',
    ];

    //-----------------------------------------------------
    // Override methods
    //-----------------------------------------------------
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    public static function boot(): void {
        parent::boot();

        // Special handling: Check relationships before deleting
        static::deleting(function ($model) {
            $relations = [
                'rRoles' => __('admin::crud.roles.title'),
            ];

            foreach ($relations as $relation => $name) {
                if ($model->{$relation}()->count() > 0) {
                    LogHandler::error('Cannot delete permission - has relationships', [
                        'permission_id' => $model->id,
                        'relation'      => $relation,
                        'count'         => $model->{$relation}()->count(),
                    ]);
                    throw new Exception(__('admin::crud.delete_has_relationship_error', [
                        'name'     => __('admin::crud.permissions.title'),
                        'relation' => $name,
                    ]));
                }
            }

            // Delete all permissions of the role
            $model->rRoles()->detach();
        });
    }

    /**
     * Override form fields.
     *
     * @param  string  $routeName  Route name
     * @param  string|null  $action  Action name
     * @return array
     */
    public static function getFormFields(string $routeName, ?string $action = null): array {
        $fields = parent::getFormFields($routeName, $action);

        // Override fields
        $fields['status']['type']    = 'select';
        $fields['status']['options'] = static::getStatusArray();

        $fields['module']['type']    = 'select';
        $fields['module']['options'] = static::getModulesArray();

        return $fields;
    }

    /**
     * Get filter fields with customization
     *
     * @param  string  $routeName
     * @return array
     */
    public static function getFilterFields(string $routeName): array {
        $fields = parent::getFilterFields($routeName);

        // Override fields
        $fields['status']['type']    = 'select';
        $fields['status']['options'] = static::getStatusArray();

        $fields['module']['type']    = 'select';
        $fields['module']['options'] = static::getModulesArray();

        return $fields;
    }

    /**
     * Get filter column mapping for display columns to database columns
     * Enhanced mapping with relationship and array support
     *
     * @return array
     */
    public static function getFilterColumnMapping(): array {
        return [
            'status' => [
                'type'   => 'array',
                'column' => 'status',
                'values' => static::getStatusArray(false),
            ],
            'module' => [
                'type'   => 'array',
                'column' => 'module',
                'values' => static::getModulesArray(false),
            ],
        ];
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------
    /**
     * Get the roles for the permission.
     *
     * @return BelongsToMany
     */
    public function rRoles() {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id');
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------
    /**
     * Get modules array for dropdown
     *
     * @param  bool  $isAddPleaseSelect
     * @return array
     */
    public static function getModulesArray($isAddPleaseSelect = true): array {
        $modules = [
            self::MODULE_ADMIN   => 'Admin',
            self::MODULE_HR      => 'HR',
            self::MODULE_CRM     => 'CRM',
            self::MODULE_API     => 'API',
            self::MODULE_MOBILES => 'Mobiles',
        ];

        // Add please select
        if ($isAddPleaseSelect) {
            $modules = ['' => __('admin::crud.please_select')] + $modules;
        }

        return $modules;
    }

    /**
     * Group permissions by module -> group
     *
     * @return array
     */
    public static function groupByModuleGroup(): array {
        $allPermissions = self::all();
        $grouped        = [];
        $modules        = self::getModulesArray(false);

        foreach ($modules as $moduleKey => $moduleName) {
            $modulePermissions = $allPermissions->where('module', $moduleKey);
            if ($modulePermissions->count() > 0) {
                $grouped[$moduleKey] = $modulePermissions->groupBy('group');
            }
        }

        $noModulePermissions = $allPermissions->filter(function ($permission) {
            return is_null($permission->module) || $permission->module === '';
        });
        if ($noModulePermissions->count() > 0) {
            $grouped[''] = $noModulePermissions->groupBy('group');
        }

        return $grouped;
    }

    /**
     * Group permissions by module -> group with filter by permission keys
     *
     * @param  array  $permissionKeys  Array of permission keys to filter
     * @return array
     */
    public static function groupByModuleGroupFiltered(array $permissionKeys): array {
        $allPermissions = self::all()->filter(function ($permission) use ($permissionKeys) {
            return in_array($permission->key, $permissionKeys, true);
        });
        $grouped        = [];
        $modules        = self::getModulesArray(false);

        foreach ($modules as $moduleKey => $moduleName) {
            $modulePermissions = $allPermissions->where('module', $moduleKey);
            if ($modulePermissions->count() > 0) {
                $grouped[$moduleKey] = $modulePermissions->groupBy('group');
            }
        }

        $noModulePermissions = $allPermissions->filter(function ($permission) {
            return is_null($permission->module) || $permission->module === '';
        });
        if ($noModulePermissions->count() > 0) {
            $grouped[''] = $noModulePermissions->groupBy('group');
        }

        return $grouped;
    }

    /**
     * Get modules array filtered by permission keys
     *
     * @param  array  $permissionKeys  Array of permission keys to filter
     * @param  bool  $isAddPleaseSelect
     * @return array
     */
    public static function getModulesArrayFiltered(array $permissionKeys, bool $isAddPleaseSelect = false): array {
        $allModules      = self::getModulesArray(false);
        $filteredModules = [];

        foreach ($allModules as $moduleKey => $moduleName) {
            $hasPermission = self::where('module', $moduleKey)
                ->whereIn('key', $permissionKeys)
                ->exists();

            if ($hasPermission) {
                $filteredModules[$moduleKey] = $moduleName;
            }
        }

        if ($isAddPleaseSelect && !empty($filteredModules)) {
            $filteredModules = ['' => __('admin::crud.please_select')] + $filteredModules;
        }

        return $filteredModules;
    }
}
