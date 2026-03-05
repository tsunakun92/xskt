<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Entities\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Class PersonalAccessToken
 *
 * Admin model for viewing and managing Sanctum personal access tokens.
 *
 * @property int $id
 * @property int $tokenable_id
 * @property string $tokenable_type
 * @property string $name
 * @property string $token
 * @property string|null $abilities
 * @property int|null $platform
 * @property string|null $device_token
 * @property int $status
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon|null $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * Accessors
 * @property string $user_name
 * @property string $platform_name
 *
 * Relationships
 * @property User $rUser
 */
class PersonalAccessToken extends AdminModel {
    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'personal_access_tokens';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'user_name',
        'platform_name',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tokenable_id',
        'tokenable_type',
        'name',
        'token',
        'abilities',
        'platform',
        'device_token',
        'status',
        'expires_at',
    ];

    /**
     * Datatable columns.
     *
     * @var array<int, string>
     */
    protected $datatableColumns = [
        'id',
        'user_name',
        'name',
        'platform_name',
        'status',
        'last_used_at',
        'expires_at',
        'created_at',
        'action',
    ];

    /**
     * Filterable columns.
     *
     * @var array<int, string>
     */
    protected $filterable = [
        'name',
        'platform',
        'status',
        'tokenable_id',
    ];

    /**
     * The attributes that should be filtered with 'LIKE' comparison.
     *
     * @var array<int, string>
     */
    protected $filterLike = [
        'name',
    ];

    /**
     * Filter panel.
     *
     * @var array<int, string>
     */
    protected $filterPanel = [
        'id',
        'user_name',
        'name',
        'platform',
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

        // Limit to user tokens only
        static::addGlobalScope('userTokens', function (Builder $builder): void {
            $builder->where('tokenable_type', User::class);
        });
    }

    /**
     * Get filter column mapping for display columns to database columns.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getFilterColumnMapping(): array {
        return [
            'status'    => [
                'type'   => 'array',
                'column' => 'status',
                'values' => static::getStatusArray(false),
            ],
            'user_name' => [
                'type'          => 'relationship',
                'column'        => 'tokenable_id',
                'relationship'  => 'rUser',
                'display_field' => 'name',
            ],
        ];
    }

    /**
     * Override form fields.
     *
     * @param  string  $routeName  Route name
     * @param  string|null  $action  Action name
     * @return array<string, array<string, mixed>>
     */
    public static function getFormFields(string $routeName, ?string $action = null): array {
        $fields = parent::getFormFields($routeName, $action);

        // User is selected via relation but not editable from token form
        if (isset($fields['tokenable_id'])) {
            $fields['tokenable_id']['type']     = 'number';
            $fields['tokenable_id']['readonly'] = true;
        }

        if (isset($fields['tokenable_type'])) {
            $fields['tokenable_type']['readonly'] = true;
        }

        if (isset($fields['platform'])) {
            $fields['platform']['type']    = 'select';
            $fields['platform']['options'] = SanctumPersonalAccessToken::getArrayPlatform();
        }

        if (isset($fields['status'])) {
            $fields['status']['type']    = 'select';
            $fields['status']['options'] = static::getStatusArray();
        }

        return $fields;
    }

    /**
     * Get filter fields with customization.
     *
     * @param  string  $routeName
     * @return array<string, array<string, mixed>>
     */
    public static function getFilterFields(string $routeName): array {
        $fields = parent::getFilterFields($routeName);

        if (isset($fields['platform'])) {
            $fields['platform']['type']    = 'select';
            $fields['platform']['options'] = SanctumPersonalAccessToken::getArrayPlatform();
        }

        if (isset($fields['status'])) {
            $fields['status']['type']    = 'select';
            $fields['status']['options'] = static::getStatusArray();
        }

        if (isset($fields['tokenable_id'])) {
            $fields['tokenable_id']['type']    = 'select';
            $fields['tokenable_id']['options'] = User::getAsDropdown(true);
        }

        return $fields;
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------
    /**
     * Get the user that owns the token.
     *
     * @return BelongsTo
     */
    public function rUser(): BelongsTo {
        return $this->belongsTo(User::class, 'tokenable_id');
    }

    //-----------------------------------------------------
    // Accessors
    //-----------------------------------------------------
    /**
     * Get the user name for the token.
     *
     * @return string
     */
    public function getUserNameAttribute(): string {
        return $this->getRelationshipValue('rUser', 'name', $this->tokenable_id);
    }

    /**
     * Get platform name.
     *
     * @return string
     */
    public function getPlatformNameAttribute(): string {
        $platforms = SanctumPersonalAccessToken::getArrayPlatform();

        return $platforms[$this->platform] ?? '';
    }
}
