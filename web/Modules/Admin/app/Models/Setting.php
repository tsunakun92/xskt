<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * Class Setting
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string|null $description
 * @property int $user_flag
 * @property int $status
 * @property int|null $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Setting extends AdminModel {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------

    /**
     * Available language options.
     * Priority: English and Japanese first.
     *
     * @var array<string, string>
     */
    public const LANGUAGES = [
        'en-US' => 'English',
        'ja'    => 'Japanese',
    ];

    /**
     * Available timezone options.
     * Priority: Common timezones first.
     *
     * @var array<string, string>
     */
    public const TIMEZONES = [
        'Asia/Tokyo'          => 'Asia/Tokyo (JST)',
        'UTC'                 => 'UTC',
        'America/New_York'    => 'America/New_York (EST/EDT)',
        'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
        'Europe/London'       => 'Europe/London (GMT/BST)',
        'Europe/Paris'        => 'Europe/Paris (CET/CEST)',
        'Asia/Shanghai'       => 'Asia/Shanghai (CST)',
        'Asia/Seoul'          => 'Asia/Seoul (KST)',
        'Australia/Sydney'    => 'Australia/Sydney (AEDT/AEST)',
    ];

    /**
     * Keys that should use CKEditor instead of textarea.
     * These keys contain HTML content that needs rich text editing.
     *
     * @var array<string>
     */
    public const EDITOR_KEYS = [
        'policy_en',
        'policy_ja',
        'policy_vn',
    ];

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------
    protected $table = 'settings';

    /** Fillable array */
    protected $fillable = [
        'key',
        'value',
        'description',
        'user_flag',
        'status',
    ];

    /**
     * Scope settings visible on user settings screen.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUserVisible($query) {
        return $query->where('user_flag', 1);
    }

    /**
     * Datatable columns array
     * NOTE: index on first column and action on last column
     */
    protected $datatableColumns = [
        'id',
        'key',
        'value',
        'description',
        'status',
        'action',
    ];

    /**
     * Filterable columns.
     *
     * @var array<int, string>
     */
    protected $filterable = [
        'key',
        'value',
        'user_flag',
        'status',
    ];

    /**
     * The attributes that should be filtered with 'LIKE' comparison.
     *
     * @var array<int, string>
     */
    protected $filterLike = ['key', 'value'];

    /**
     * Filter panel
     *
     * @var array<int, string>
     */
    protected $filterPanel = [
        'id',
        'key',
        'value',
        'description',
        'user_flag',
        'status',
    ];

    /**
     * Static cache for settings
     *
     * @var array|null
     */
    protected static $aSettings = null;

    /**
     * Simple request-level caches to mitigate N+1 when fetching user settings.
     *
     * @var array<int, User|null>
     */
    protected static array $userCache = [];

    /**
     * @var array<string, self|null>
     */
    protected static array $settingCache = [];

    /**
     * @var array<string, object|null>
     */
    protected static array $userSettingCache = [];

    /**
     * Select value for dropdown
     *
     * @var string
     */
    protected static $selectValue = 'value';

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

        // Special handling: Clear cache on create/update/delete
        static::created(function ($model) {
            self::$aSettings        = null;
            self::$settingCache     = [];
            self::$userSettingCache = [];
        });

        static::updated(function ($model) {
            self::$aSettings        = null;
            self::$settingCache     = [];
            self::$userSettingCache = [];
        });

        static::deleted(function ($model) {
            self::$aSettings        = null;
            self::$settingCache     = [];
            self::$userSettingCache = [];
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
        $fields['status']['options'] = static::getStatusArray(true);

        // Add user_flag dropdown
        $fields['user_flag']['type']    = 'select';
        $fields['user_flag']['options'] = static::getUserFlagArray(true);
        $fields['user_flag']['value']   = 0; // Default value

        // Check if editing existing record to get the key
        $key = null;

        // Try to get key from route parameter (for edit)
        $routeParam = request()->route('setting') ?? request()->route('id');
        if ($routeParam) {
            $model = $routeParam instanceof static ? $routeParam : static::find($routeParam);
            if ($model) {
                $key = $model->key;
            }
        }

        // Fallback to old input (for validation errors or create with pre-filled key)
        if (!$key) {
            $key = request()->old('key');
        }

        // If key matches editor keys, change type to editor
        if ($key && isset($fields['value']) && in_array($key, static::EDITOR_KEYS, true)) {
            $fields['value']['type'] = 'editor';
        }

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
        $fields['status']['options'] = static::getStatusArray(true);

        // Add user_flag filter dropdown
        if (isset($fields['user_flag'])) {
            $fields['user_flag']['type']    = 'select';
            $fields['user_flag']['options'] = static::getUserFlagArray(true);
        }

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
            'status'    => [
                'type'   => 'array',
                'column' => 'status',
                'values' => static::getStatusArray(false),
            ],
            'user_flag' => [
                'type'   => 'array',
                'column' => 'user_flag',
                'values' => static::getUserFlagArray(false),
            ],
        ];
    }

    /**
     * Get custom column renderers for datatables
     * Returns array of column => view path mappings
     *
     * @return array
     */
    public static function getCustomColumnRenderers(): array {
        return [
            'value' => 'admin::datatables.setting-value',
        ];
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------
    /**
     * Get the users for the setting.
     *
     * @return BelongsToMany
     */
    public function rUsers() {
        return $this->belongsToMany(User::class, 'user_settings', 'setting_id', 'user_id')
            ->withPivot('value');
    }

    //-----------------------------------------------------
    // Utility methods
    //-----------------------------------------------------
    /**
     * Get user flag array for dropdown
     *
     * @param  bool  $includePleaseSelect  Whether to include "Please Select" option
     * @return array
     */
    public static function getUserFlagArray(bool $includePleaseSelect = false): array {
        $options = [
            0 => __('admin::crud.settings.user_flag_no'),
            1 => __('admin::crud.settings.user_flag_yes'),
        ];

        if ($includePleaseSelect) {
            return ['' => __('admin::crud.please_select')] + $options;
        }

        return $options;
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------
    /**
     * Get value by key
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null) {
        if (self::$aSettings === null) {
            self::$aSettings = self::pluck('value', 'key')->toArray();
        }

        return self::$aSettings[$key] ?? $default;
    }

    /**
     * Check if maintenance mode is enabled
     *
     * @return bool
     */
    public static function isMaintenance(): bool {
        return (bool) self::getValue('maintenance_mode', false);
    }

    /**
     * Get last sync time
     *
     * @return string|null
     */
    public static function getLastSyncTime(): ?string {
        return self::getValue('last_sync_time');
    }

    /**
     * Get rules
     *
     * @return array
     */
    public static function getRules(): array {
        $rulesJson = self::getValue('rules', '[]');
        $rules     = json_decode($rulesJson, true);

        return is_array($rules) ? $rules : [];
    }

    /**
     * Get show link
     *
     * @return bool
     */
    public static function getShowLink(): bool {
        return (bool) self::getValue('show_link', true);
    }

    /**
     * Get value for user (with override from user_settings)
     *
     * @param  int  $userId
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function getValueForUser(int $userId, string $key, $default = null) {
        $user = self::$userCache[$userId] ??= User::find($userId);
        if (!$user) {
            return $default;
        }

        // If super admin, check global setting first
        if ($user->isSuperAdmin()) {
            $globalValue = self::getValue($key);
            if ($globalValue !== null) {
                return $globalValue;
            }
        }

        // Get setting by key
        $setting = self::$settingCache[$key] ??= self::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        // Check if user has override value
        $userSettingCacheKey  = "{$userId}:{$setting->id}";
        $userSetting          = self::$userSettingCache[$userSettingCacheKey] ??= DB::table('user_settings')
            ->where('user_id', $userId)
            ->where('setting_id', $setting->id)
            ->first();

        if ($userSetting) {
            return $userSetting->value;
        }

        // Fallback to global setting value
        return $setting->status === self::STATUS_ACTIVE ? $setting->value : $default;
    }

    /**
     * Set value for user (override global setting)
     *
     * @param  int  $userId
     * @param  string  $key
     * @param  mixed  $value
     * @return bool
     */
    public static function setValueForUser(int $userId, string $key, $value): bool {
        $user = self::$userCache[$userId] ??= User::find($userId);
        if (!$user) {
            return false;
        }

        // If super admin, can't override global setting
        if ($user->isSuperAdmin()) {
            $globalValue = self::getValue($key);
            if ($globalValue !== null) {
                return false;
            }
        }

        // Get setting by key
        $setting = self::$settingCache[$key] ??= self::where('key', $key)->first();
        if (!$setting) {
            return false;
        }

        // Check if user setting exists
        $cacheKey = "{$userId}:{$setting->id}";
        $exists   = self::$userSettingCache[$cacheKey] ??= DB::table('user_settings')
            ->where('user_id', $userId)
            ->where('setting_id', $setting->id)
            ->exists();

        if ($exists) {
            // Update existing
            DB::table('user_settings')
                ->where('user_id', $userId)
                ->where('setting_id', $setting->id)
                ->update(['value' => $value]);
        } else {
            // Create new
            DB::table('user_settings')->insert([
                'user_id'    => $userId,
                'setting_id' => $setting->id,
                'value'      => $value,
            ]);
        }

        return true;
    }
}
