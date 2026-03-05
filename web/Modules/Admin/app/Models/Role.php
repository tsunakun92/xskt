<?php

namespace Modules\Admin\Models;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

use App\Utils\DomainConst;
use Modules\Logging\Utils\LogHandler;

/**
 * Class Role
 *
 * @property int $id ID of record
 * @property string $name Name
 * @property string $code Code
 * @property int $status Status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Illuminate\Database\Eloquent\Collection|User[] $rUsers
 * @property \Illuminate\Database\Eloquent\Collection|Permission[] $rPermissions
 */
class Role extends AdminModel {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------
    const ROLE_SUPER_ADMIN_CODE   = 'ROLE_SUPER_ADMIN';

    const ROLE_ADMIN_CODE         = 'ROLE_ADMIN';

    const ROLE_CUSTOMER_CODE      = 'ROLE_CUSTOMER';

    const ROLE_STAFF_CODE         = 'ROLE_STAFF';

    const ROLE_STAFF_MANAGER_CODE = 'ROLE_STAFF_MANAGER';

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------
    /** Fillable array */
    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    /**
     * Datatable columns.
     *
     * @var array<int, string>
     */
    protected $datatableColumns = [
        'id',
        'name',
        'code',
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
        'code',
        'status',
    ];

    /**
     * The attributes that should be filtered with 'LIKE' comparison.
     *
     * @var array<int, string>
     */
    protected $filterLike = ['name', 'code'];

    /**
     * Filter panel
     *
     * @var array<int, string>
     */
    protected $filterPanel = [
        'id',
        'name',
        'code',
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
                'rUsers' => __('admin::crud.users.title'),
            ];

            foreach ($relations as $relation => $name) {
                if ($model->{$relation}()->count() > 0) {
                    LogHandler::error('Cannot delete role - has relationships', [
                        'role_id'  => $model->id,
                        'relation' => $relation,
                        'count'    => $model->{$relation}()->count(),
                    ]);
                    throw new Exception(__('admin::crud.delete_has_relationship_error', [
                        'name'     => __('admin::crud.roles.title'),
                        'relation' => $name,
                    ]));
                }
            }

            // Delete all permissions of the role
            $model->rPermissions()->detach();

            // Clear role cache when role is deleted
            self::clearRoleCache();
        });

        // Clear role cache when role is updated
        static::updated(function ($model) {
            self::clearRoleCache();
        });

        // Clear role cache when role is created
        static::created(function ($model) {
            self::clearRoleCache();
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
        ];
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------
    /**
     * Get the users for the role (via role_id).
     *
     * @return HasMany
     */
    public function rUsers() {
        return $this->hasMany(User::class, 'role_id');
    }

    /**
     * Get the users assigned to this role via one_many.
     *
     * @return BelongsToMany
     */
    public function rUsersViaOneMany() {
        return $this->belongsToMany(User::class, 'one_many', 'many_id', 'one_id')
            ->wherePivot('type', OneMany::TYPE_USER_ROLE)
            ->wherePivot('status', self::STATUS_ACTIVE)
            ->withTimestamps();
    }

    /**
     * Get the permissions for the role.
     *
     * @return BelongsToMany
     */
    public function rPermissions() {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id');
    }

    //-----------------------------------------------------
    // Utility methods
    //-----------------------------------------------------
    /**
     * Can access
     *
     * @param  string  $permissionKey
     * @return bool
     */
    public function canAccess(string $permissionKey): bool {
        $cacheKey = "role:{$this->id}:permissions";

        $permissionMap = static::rememberCache($cacheKey, function () {
            $keys = $this->rPermissions()
                ->pluck('key')
                ->toArray();

            return array_fill_keys($keys, true);
        });

        return isset($permissionMap[$permissionKey]);
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------
    /**
     * Get the role by code.
     *
     * @param  string  $code
     * @return Role|null
     */
    public static function getByCode(string $code): ?Role {
        $cacheKey = "role:code:{$code}";

        return static::rememberCache($cacheKey, function () use ($code) {
            return self::where('code', $code)->first();
        });
    }

    /**
     * Override getAsDatatables to exclude super admin role from DataTables display.
     *
     * @param  array  $filters  Filter parameters from DataTables
     * @param  string  $sortBy  Column to sort by
     * @param  string  $sortDirection  Sort direction (asc/desc)
     * @return Builder
     */
    public static function getAsDatatables(array $filters = [], string $sortBy = 'id', string $sortDirection = 'asc'): Builder {
        $query = parent::getAsDatatables($filters, $sortBy, $sortDirection);

        // Always exclude super admin role from admin-facing listings
        return $query->where('code', '!=', self::ROLE_SUPER_ADMIN_CODE);
    }

    /**
     * Declare cache patterns to clear on lifecycle events.
     *
     * @return array<int, string>
     */
    public function getCacheClearPatterns(): array {
        return [
            'role:code:*',
            "role:{$this->id}:permissions",
        ];
    }

    /**
     * Clear all role related cache keys.
     *
     * @return void
     */
    public static function clearRoleCache(): void {
        // Clear all cached role codes
        static::forgetCachePattern('role:code:*');

        // Clear all cached role permissions for all roles
        static::forgetCachePattern('role:*:permissions');
    }

    /**
     * Get as dropdown
     *
     * @param  bool  $isAddPleaseSelect
     * @param  string  $key  Column used as label (default: name)
     * @param  string  $value  Column used as value (default: id)
     * @return array
     */
    public static function getAsDropdown(bool $isAddPleaseSelect = true, string $key = 'name', string $value = 'id'): array {
        // Get all items active
        $query = self::active()->where('code', '!=', self::ROLE_SUPER_ADMIN_CODE);

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
     * Check role has permission
     *
     * @param  string  $permissionKey
     * @param  int  $roleId
     * @return bool
     */
    public static function hasPermission(string $permissionKey, int $roleId): bool {
        $role = self::find($roleId);

        return $role->canAccess($permissionKey);
    }

    /**
     * Get current permissions
     *
     * @return array
     */
    public function getCurrentPermissions(): array {
        $currentPermissions = [];
        $rolePermissions    = $this->rPermissions()->get();

        foreach ($rolePermissions as $permission) {
            $currentPermissions[] = [
                'id'  => $permission->id,
                'key' => $permission->key,
            ];
        }

        return $currentPermissions;
    }

    /**
     * Check if role is super admin
     *
     * @return bool
     */
    public function isSuperAdmin(): bool {
        return $this->code === self::ROLE_SUPER_ADMIN_CODE;
    }

    /**
     * Sync role permissions
     *
     * @param  array  $permissionKeys  Array of permission keys to sync
     *
     * @throws Exception
     *
     * @return void
     */
    public function syncPermissions(array $permissionKeys): void {
        DB::transaction(function () use ($permissionKeys) {
            if (empty($permissionKeys)) {
                DB::table('role_permissions')
                    ->where('role_id', $this->id)
                    ->delete();

                return;
            }

            // Validate: Check if all permission keys exist
            $foundKeys = Permission::whereIn('key', $permissionKeys)
                ->pluck('key')
                ->toArray();

            $missingKeys = array_diff($permissionKeys, $foundKeys);

            if (!empty($missingKeys)) {
                LogHandler::warning('Some permission keys do not exist when syncing role permissions', [
                    'role_id'       => $this->id,
                    'missing_keys'  => $missingKeys,
                    'provided_keys' => $permissionKeys,
                ]);

                throw new Exception('Cannot sync permissions: Some permission keys do not exist: ' . implode(', ', $missingKeys));
            }

            // Get permission IDs for the given keys
            $permissionIds = Permission::whereIn('key', $permissionKeys)
                ->pluck('id')
                ->toArray();

            // Sync the permissions
            $this->rPermissions()->sync($permissionIds);
        });

        // Clear cache for both empty and non-empty cases
        $this->clearRoleCache();
    }
}
