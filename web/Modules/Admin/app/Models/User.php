<?php

namespace Modules\Admin\Models;

use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

use App\Entities\Sanctum\PersonalAccessToken as AppPersonalAccessToken;
use App\Models\BaseModel;
use App\Utils\DateTimeExt;
use App\Utils\DomainConst;
use Modules\Admin\Database\Factories\UserFactory;
use Modules\Logging\Utils\LogHandler;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $username
 * @property int $role_id
 * @property int $status
 * @property \Carbon\Carbon $email_verified_at
 * @property \Carbon\Carbon|null $last_date_login
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * Accessors
 * @property string $role_name
 *
 * Relationships
 * @property Role $rRole Role object
 * @property \Illuminate\Database\Eloquent\Collection|Permission[] $rPermissions Permission objects
 * @property \Illuminate\Database\Eloquent\Collection|Setting[] $rSettings Setting objects
 */
class User extends AdminModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract {
    use Authenticatable, Authorizable, CanResetPassword, HasApiTokens, HasFactory, MustVerifyEmail, Notifiable;

    protected $table = 'users';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['role_name'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'password',
        'name',
        'email',
        'role_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Datatable columns.
     *
     * @var array<int, string>
     */
    protected $datatableColumns = [
        'id',
        'name',
        'email',
        'username',
        'role_name',
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
        'email',
        'username',
        'role_id',
        'status',
    ];

    /**
     * The attributes that should be filtered with 'LIKE' comparison.
     *
     * @var array<int, string>
     */
    protected $filterLike = ['name', 'email', 'username'];

    /**
     * Filter panel
     *
     * @var array<int, string>
     */
    protected $filterPanel = [
        'id',
        'name',
        'email',
        'username',
        'role_name',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'last_date_login'   => 'datetime',
            'password'          => 'hashed',
        ];
    }

    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------

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

        // Special handling: Logout if update authenticated user
        static::updated(function ($model) {
            // logout if update authenticated user (only for session-based auth, not Sanctum)
            if (auth()->check() && auth()->user()->id === $model->id) {
                $checkFields = ['password', 'username', 'email', 'role_id', 'status'];

                foreach ($checkFields as $field) {
                    if ($model->isDirty($field)) {
                        LogHandler::warning('User logged out due to important information change', [
                            'user_id'       => $model->id,
                            'changed_field' => $field,
                        ]);

                        // Only logout for session-based auth (web guard), not for Sanctum
                        if (auth()->getDefaultDriver() === 'web' && method_exists(auth()->guard(), 'logout')) {
                            auth()->logout();
                            if (request()->hasSession()) {
                                request()->session()->invalidate();
                                request()->session()->regenerateToken();
                            }
                        }
                        break;
                    }
                }
            }
        });

        // Special handling: Delete related records when user is deleted
        static::deleting(function ($model) {
            // Delete all user permissions
            DB::table('user_permissions')
                ->where('user_id', $model->id)
                ->delete();

            // Delete all user settings
            DB::table('user_settings')
                ->where('user_id', $model->id)
                ->delete();

            // Clear user permission cache
            $model->clearUserPermissionCache();

            LogHandler::info('User related records deleted', [
                'user_id' => $model->id,
            ]);
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory() {
        return UserFactory::new();
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
        $fields['email']['type']         = 'email';
        $fields['password']['type']      = 'password';
        $fields['role_id']['type']       = 'select';
        $fields['role_id']['options']    = Role::getAsDropdown();

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
        $fields['role_id']['type']     = 'select';
        $fields['role_id']['options']  = Role::getAsDropdown();
        $fields['status']['type']      = 'select';
        $fields['status']['options']   = static::getStatusArray();

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
            'role_name' => [
                'type'          => 'relationship',
                'column'        => 'role_id',
                'relationship'  => 'rRole',
                'display_field' => 'name',
            ],
            'status'    => [
                'type'   => 'array',
                'column' => 'status',
                'values' => static::getStatusArray(false),
            ],
        ];
    }

    /**
     * Get array status
     *
     * @param  bool  $addPleaseSelect
     * @return array
     */
    public static function getStatusArray(bool $addPleaseSelect = true): array {
        $status = [
            self::STATUS_INACTIVE         => __('admin::app.inactive'),
            self::STATUS_ACTIVE           => __('admin::app.active'),
            self::STATUS_REGISTER_REQUEST => __('admin::app.register_request'),
        ];

        if ($addPleaseSelect) {
            $status = [DomainConst::VALUE_EMPTY => __('admin::crud.please_select')] + $status;
        }

        return $status;
    }

    //-----------------------------------------------------
    // Filter methods
    //-----------------------------------------------------
    /**
     * Filter role
     *
     * @param  Builder  $query
     * @param  mixed  $value
     * @return Builder
     */
    public function filterRolesId($query, $value) {
        // skip if value is <= 0
        if ($value <= 0) {
            return $query;
        }

        return $query->where('role_id', $value);
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------

    /**
     * Relationship to Role
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rRole() {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Relationship to Permissions
     *
     * @return BelongsToMany
     */
    public function rPermissions() {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id');
    }

    /**
     * Relationship to Settings
     *
     * @return BelongsToMany
     */
    public function rSettings() {
        return $this->belongsToMany(Setting::class, 'user_settings', 'user_id', 'setting_id')
            ->withPivot('value');
    }

    //-----------------------------------------------------
    // Accessors
    //-----------------------------------------------------
    /**
     * Get the name for the user's status.
     *
     * @return string
     */
    public function getStatusNameAttribute(): string {
        return $this->getStatusName();
    }

    /**
     * Get the name for the user's role.
     *
     * @return string
     */
    public function getRoleNameAttribute(): string {
        return $this->getRelationshipValue('rRole', 'name', $this->role_id);
    }

    //-----------------------------------------------------
    // Utility methods
    //-----------------------------------------------------
    /**
     * Check if the user is super admin.
     *
     * @return bool
     */
    public function isSuperAdmin(): bool {
        $role = $this->rRole;

        if (!$role && $this->role_id) {
            $role = $this->rRole()->first();
        }

        return $role ? $role->code === Role::ROLE_SUPER_ADMIN_CODE : false;
    }

    /**
     * Check user is admin.
     *
     * @return bool
     */
    public function isAdmin(): bool {
        $role = $this->rRole;

        if (!$role && $this->role_id) {
            $role = $this->rRole()->first();
        }

        return $role ? $role->code === Role::ROLE_ADMIN_CODE : false;
    }

    /**
     * Check user is super-admin or admin
     *
     * @return bool
     */
    public function isRoledAdmin(): bool {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    /**
     * Check if user has permission for given key.
     *
     * Logic:
     * - If user has custom permissions: must exist in both user_permissions AND role_permissions
     * - Otherwise: fallback to role_permissions only
     *
     * Uses cache per user to avoid duplicate permission lookups.
     *
     * @param  string  $permissionKey
     * @return bool
     */
    public function canAccess(string $permissionKey): bool {
        $cacheKey = "user:{$this->id}:permissions";

        $userPermissions = static::rememberCache($cacheKey, function () {
            return $this->rPermissions()
                ->pluck('key')
                ->toArray();
        });

        if (!empty($userPermissions)) {
            $hasUserPermission = in_array($permissionKey, $userPermissions, true);
            $hasRolePermission = $this->rRole?->canAccess($permissionKey) ?? false;

            return $hasUserPermission && $hasRolePermission;
        }

        return $this->rRole?->canAccess($permissionKey) ?? false;
    }

    /**
     * Check if user is first login today
     *
     * @return bool True if last date login is not today, false otherwise
     */
    public function isFirstLogin(): bool {
        if (!$this->last_date_login) {
            return true;
        }
        $today         = DateTimeExt::getCurrentDateTime(DateTimeExt::DATE_FORMAT_4);
        $lastDateLogin = DateTimeExt::formatDateTime($this->last_date_login, DateTimeExt::DATE_FORMAT_4);
        if ($lastDateLogin != $today) {
            return true;
        }

        return false;
    }

    /**
     * Update last_date_login when logged in
     *
     * @return bool
     */
    public function updateLastLogin(): bool {
        $this->last_date_login = DateTimeExt::getCurrentDateTime();

        return $this->save();
    }

    /**
     * Get current permissions.
     * If user has specific permissions, return those.
     * Otherwise, fallback to role permissions (default behavior).
     *
     * @return array
     */
    public function getCurrentPermissions(): array {
        $currentPermissions = [];
        $userPermissions    = $this->rPermissions()->get();

        // If user has specific permissions, use those
        if ($userPermissions->count() > 0) {
            foreach ($userPermissions as $permission) {
                $currentPermissions[] = [
                    'id'  => $permission->id,
                    'key' => $permission->key,
                ];
            }
        } else {
            // Fallback to role permissions if user has no specific permissions
            if ($this->rRole) {
                $rolePermissions = $this->rRole->rPermissions()->get();

                foreach ($rolePermissions as $permission) {
                    $currentPermissions[] = [
                        'id'  => $permission->id,
                        'key' => $permission->key,
                    ];
                }
            }
        }

        return $currentPermissions;
    }

    /**
     * Get role permission keys
     *
     * @return array Array of permission keys
     */
    public function getRolePermissionKeys(): array {
        if (!$this->rRole) {
            return [];
        }

        return $this->rRole->rPermissions()
            ->pluck('key')
            ->toArray();
    }

    /**
     * Validate if user can access permission management
     *
     * @return array|null Returns error message array if validation fails, null if valid
     */
    public function validatePermissionAccess(): ?array {
        // Check if user has a role
        if (!$this->rRole) {
            return [
                'message' => __('admin::crud.user_has_no_role'),
            ];
        }

        // Check if role has any permissions
        $rolePermissionKeys  = $this->getRolePermissionKeys();
        $hasAnyPermission    = count($rolePermissionKeys) > 0;

        if (!$hasAnyPermission) {
            return [
                'message' => __('admin::crud.role_has_no_permissions'),
            ];
        }

        return null;
    }

    /**
     * Get filtered permissions based on role permissions
     *
     * @return array Filtered permissions array
     */
    public function getFilteredPermissions(): array {
        $rolePermissionKeys  = $this->getRolePermissionKeys();
        $allPermissions      = Permission::all();
        $filteredPermissions = [];

        foreach ($allPermissions as $permission) {
            if (in_array($permission->key, $rolePermissionKeys, true)) {
                $filteredPermissions[] = $permission;
            }
        }

        return $filteredPermissions;
    }

    /**
     * Sync user permissions
     *
     * @param  array  $permissionKeys  Array of permission keys to sync
     *
     * @throws Exception
     *
     * @return void
     */
    public function syncPermissions(array $permissionKeys): void {
        // If empty, remove all user permissions
        if (empty($permissionKeys)) {
            DB::table('user_permissions')
                ->where('user_id', $this->id)
                ->delete();

            $this->clearUserPermissionCache();

            return;
        }

        // Validate: Check if all permission keys exist
        $foundPermissions = Permission::whereIn('key', $permissionKeys)->get();
        $foundKeys        = $foundPermissions->pluck('key')->toArray();
        $missingKeys      = array_diff($permissionKeys, $foundKeys);

        if (!empty($missingKeys)) {
            LogHandler::warning('Some permission keys do not exist when syncing user permissions', [
                'user_id'       => $this->id,
                'missing_keys'  => $missingKeys,
                'provided_keys' => $permissionKeys,
            ]);
        }

        // Validate and collect valid permission IDs
        $validPermissionIds = [];

        foreach ($foundPermissions as $permission) {
            // Validate: User permission must be within role permissions
            if (!$this->rRole || !$this->rRole->canAccess($permission->key)) {
                LogHandler::warning('Cannot sync user permission - role does not have this permission', [
                    'user_id'        => $this->id,
                    'permission_id'  => $permission->id,
                    'permission_key' => $permission->key,
                ]);

                continue;
            }

            $validPermissionIds[] = $permission->id;
        }

        // Use transaction to ensure atomicity
        DB::transaction(function () use ($validPermissionIds) {
            // Remove all existing permissions for this user
            DB::table('user_permissions')
                ->where('user_id', $this->id)
                ->delete();

            // Insert valid permissions
            if (!empty($validPermissionIds)) {
                $insertData = array_map(function ($permissionId) {
                    return [
                        'user_id'       => $this->id,
                        'permission_id' => $permissionId,
                    ];
                }, $validPermissionIds);

                DB::table('user_permissions')->insert($insertData);
            }
        });

        $this->clearUserPermissionCache();
    }

    /**
     * Get all settings with user override values (or default from Settings)
     *
     * @return array Format: [['id' => x, 'key' => 'y', 'value' => 'z', 'default_value' => 'w', 'description' => '...', 'is_override' => bool], ...]
     */
    public function getUserSettingsWithDefaults(bool $onlyUserVisible = false): array {
        $allSettingsQuery = Setting::active();
        if ($onlyUserVisible) {
            $allSettingsQuery->userVisible();
        }
        $allSettings  = $allSettingsQuery->get();
        $userSettings = $this->rSettings()->get()->keyBy('id');
        $result       = [];

        foreach ($allSettings as $setting) {
            $userSetting = $userSettings->get($setting->id);
            $isOverride  = $userSetting !== null;

            $result[] = [
                'id'            => $setting->id,
                'key'           => $setting->key,
                'value'         => $isOverride ? $userSetting->pivot->value : $setting->value,
                'default_value' => $setting->value,
                'description'   => $setting->description,
                'is_override'   => $isOverride,
            ];
        }

        return $result;
    }

    /**
     * Sync user settings
     * Remove all existing user settings and insert new ones
     *
     * @param  array  $requestSettings  Format: settings[setting_id] = value
     * @return void
     */
    public function syncSettings(array $requestSettings): void {
        // If super admin, can't override settings
        if ($this->isSuperAdmin()) {
            return;
        }

        // Batch load active settings to avoid N+1
        $settingIds    = array_keys($requestSettings);
        $activeSetting = Setting::whereIn('id', $settingIds)
            ->active()
            ->pluck('id')
            ->toArray();

        // Remove all existing user settings
        DB::table('user_settings')
            ->where('user_id', $this->id)
            ->delete();

        // Prepare bulk insert
        $insertData = [];
        foreach ($requestSettings as $settingId => $value) {
            $settingId = (int) $settingId;
            if ($value === null || $value === '' || !in_array($settingId, $activeSetting, true)) {
                continue; // Skip empty values or inactive/invalid settings
            }

            $insertData[] = [
                'user_id'    => $this->id,
                'setting_id' => $settingId,
                'value'      => $value,
            ];
        }

        if (!empty($insertData)) {
            DB::table('user_settings')->insert($insertData);
        }
    }

    /**
     * Get user settings for API response.
     * Only returns settings with user_flag=1 (user-visible settings).
     *
     * @return array
     */
    public function apiGetUserSettings(): array {
        $settings = $this->getUserSettingsWithDefaults(true); // Only user-visible settings
        $result   = [
            'language'             => 'en',
            'timezone'             => 'UTC',
            'notification_enabled' => true,
            'dark_mode'            => false,
        ];

        foreach ($settings as $setting) {
            switch ($setting['key']) {
                case 'language':
                    $result['language'] = $setting['value'];
                    break;
                case 'timezone':
                    $result['timezone'] = $setting['value'];
                    break;
                case 'notification_enabled':
                    $result['notification_enabled'] = (bool) $setting['value'];
                    break;
                case 'dark_mode':
                    $result['dark_mode'] = (bool) $setting['value'];
                    break;
            }
        }

        return $result;
    }

    /**
     * Get list of assigned roles for API response
     * Includes the main role (users.role_id) and all roles from one_many
     *
     * @return array<int, array{id: int, name: string, status: int}>
     */
    public function apiGetListRoles(): array {
        $roles   = [];
        $roleIds = [];

        // Add main role if exists
        if ($this->role_id && $this->rRole) {
            $roles[] = [
                'id'     => $this->rRole->id,
                'name'   => $this->rRole->name,
                'status' => $this->rRole->status,
            ];
            $roleIds[] = $this->rRole->id;
        }

        // Add roles from one_many (excluding main role if already added)
        $assignedRoles = $this->rAssignedRoles()->whereNotIn('roles.id', $roleIds)->get();
        foreach ($assignedRoles as $role) {
            $roles[] = [
                'id'     => $role->id,
                'name'   => $role->name,
                'status' => $role->status,
            ];
        }

        return $roles;
    }

    /**
     * Get list of assigned sections for API response
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function apiGetListSections(): array {
        $sections         = [];
        $assignedSections = $this->rAssignedSections()->get();

        foreach ($assignedSections as $section) {
            $sections[] = [
                'id'   => $section->id,
                'name' => $section->name,
            ];
        }

        return $sections;
    }

    /**
     * Get user permissions for API response
     * Groups permissions by controller and returns in format: [{id, name, data: [{id, name, controller_id, actions}]}]
     *
     * @return array
     */
    public function apiGetUserPermission(): array {
        $permissions         = $this->getFilteredPermissions();
        $groupedByController = [];

        // Group permissions by controller (first part of permission key)
        foreach ($permissions as $permission) {
            // Parse permission key (e.g., "users.index" -> controller: "users", action: "index")
            $keyParts   = explode('.', $permission->key);
            $controller = $keyParts[0] ?? $permission->key;
            $action     = $keyParts[1] ?? '';

            if (!isset($groupedByController[$controller])) {
                $groupedByController[$controller] = [
                    'id'   => $permission->id,
                    'name' => $controller,
                    'data' => [],
                ];
            }

            // Add permission as Group to data array
            $groupedByController[$controller]['data'][] = [
                'id'            => $permission->id,
                'name'          => $permission->key,
                'controller_id' => (string) $permission->id,
                'actions'       => $action,
            ];
        }

        return array_values($groupedByController);
    }

    /**
     * Create a new personal access token with device_token and platform
     *
     * @param  string  $name  Token name
     * @param  string|null  $deviceToken  Device token
     * @param  int|null  $platform  Platform (1 = Web, 2 = Android, 3 = iOS, 4 = Windows)
     * @param  array  $abilities  Token abilities
     * @return \Laravel\Sanctum\NewAccessToken
     */
    public function createToken(string $name, ?string $deviceToken = null, ?int $platform = null, array $abilities = ['*']) {
        $token = $this->tokens()->create([
            'name'         => $name,
            'token'        => hash('sha256', $plainTextToken = \Illuminate\Support\Str::random(40)),
            'abilities'    => $abilities,
            'device_token' => $deviceToken,
            'platform'     => $platform ?? AppPersonalAccessToken::PLATFORM_WEB,
            'status'       => BaseModel::STATUS_ACTIVE,
        ]);

        return new \Laravel\Sanctum\NewAccessToken($token, $token->id . '|' . $plainTextToken);
    }

    /**
     * Clear user permission cache.
     *
     * @return void
     */
    public function clearUserPermissionCache(): void {
        static::forgetCachePattern("user:{$this->id}:permissions");
    }

    /**
     * Declare cache patterns to clear on lifecycle events.
     *
     * @return array<int, string>
     */
    public function getCacheClearPatterns(): array {
        return [
            "user:{$this->id}:permissions",
        ];
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------
    /**
     * Override getAsDatatables to exclude super admin users from DataTables display.
     *
     * @param  array  $filters  Filter parameters from DataTables
     * @param  string  $sortBy  Column to sort by
     * @param  string  $sortDirection  Sort direction (asc/desc)
     * @return Builder
     */
    public static function getAsDatatables(array $filters = [], string $sortBy = 'id', string $sortDirection = 'asc'): Builder {
        $query = parent::getAsDatatables($filters, $sortBy, $sortDirection);

        $superAdminRole = Role::getByCode(Role::ROLE_SUPER_ADMIN_CODE);

        // If super admin role exists, always exclude users with that role from admin-facing listings
        if ($superAdminRole) {
            $query->where('role_id', '!=', $superAdminRole->id);
        }

        return $query;
    }

    /**
     * Get users as dropdown, excluding super admin users.
     *
     * @param  bool  $isAddPleaseSelect
     * @param  string  $key  Column used as label (default: name)
     * @param  string  $value  Column used as value (default: id)
     * @return array
     */
    public static function getAsDropdown(bool $isAddPleaseSelect = true, string $key = 'name', string $value = 'id'): array {
        $query = self::active()->whereHas('rRole', function (Builder $builder) {
            $builder->where('code', '!=', Role::ROLE_SUPER_ADMIN_CODE);
        });

        $dropdown = $query->pluck($key, $value);

        if ($isAddPleaseSelect) {
            $dropdown = $dropdown->prepend(__('admin::crud.please_select'), DomainConst::VALUE_ZERO);
        }

        return $dropdown->toArray();
    }
}
