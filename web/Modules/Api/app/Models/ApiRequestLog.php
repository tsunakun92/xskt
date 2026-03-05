<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Utils\DomainConst;
use Modules\Admin\Models\User;

/**
 * Class ApiRequestLog
 *
 * API request logs for tracking incoming API requests and responses.
 *
 * @property int $id ID of record
 * @property string|null $ip_address IP address
 * @property string|null $country Country
 * @property int|null $user_id User ID
 * @property string|null $method HTTP method and path
 * @property string|null $content Request content
 * @property string|null $response Response content
 * @property int $status Status
 * @property int|null $created_by Created by user ID
 * @property \Carbon\Carbon|null $responsed_date Response date time
 * @property \Carbon\Carbon $created_at Created at timestamp
 * @property \Carbon\Carbon $updated_at Updated at timestamp
 *
 * Accessors
 * @property string $user_name
 *
 * Relationships
 * @property User $rUser
 */
class ApiRequestLog extends ApiModel {
    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'api_request_logs';

    /**
     * Fillable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip_address',
        'country',
        'user_id',
        'method',
        'content',
        'response',
        'status',
        'responsed_date',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'user_name',
    ];

    /**
     * Datatable columns configuration.
     *
     * @var array<int, string>
     */
    protected $datatableColumns = [
        'id',
        'ip_address',
        'country',
        'user_name',
        'method',
        'status',
        'responsed_date',
    ];

    /**
     * Filterable columns.
     *
     * @var array<int, string>
     */
    protected $filterable = [
        'ip_address',
        'country',
        'user_id',
        'status',
    ];

    /**
     * Columns that should use LIKE comparison.
     *
     * @var array<int, string>
     */
    protected $filterLike = [
        'ip_address',
        'country',
        'method',
    ];

    /**
     * Get datatable table columns.
     *
     * @return array<string, string>
     */
    public static function getDatatableTableColumns(): array {
        return self::getBaseDatatableTableColumns();
    }

    /**
     * Get filter column mapping.
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
                'column'        => 'user_id',
                'relationship'  => 'rUser',
                'display_field' => 'name',
            ],
        ];
    }

    //-----------------------------------------------------
    // Override methods
    //-----------------------------------------------------

    /**
     * Override form fields for ApiRequestLog.
     * Although logs are read-only, this keeps model consistent with template.
     *
     * @param  string  $routeName
     * @param  string|null  $action
     * @return array<string, array<string, mixed>>
     */
    public static function getFormFields(string $routeName, ?string $action = null): array {
        $fields = parent::getFormFields($routeName, $action);

        // Override fields
        if (isset($fields['user_id'])) {
            $fields['user_id']['type']    = 'select';
            $fields['user_id']['options'] = User::getAsDropdown();
        }

        if (isset($fields['status'])) {
            $fields['status']['type']    = 'select';
            $fields['status']['options'] = static::getStatusArray();
        }

        return $fields;
    }

    /**
     * Override filter fields for ApiRequestLog to customize status field.
     *
     * @param  string  $routeName
     * @return array<string, array<string, mixed>>
     */
    public static function getFilterFields(string $routeName): array {
        $fields = parent::getFilterFields($routeName);

        // Override fields
        if (isset($fields['user_id'])) {
            $fields['user_id']['type']    = 'select';
            $fields['user_id']['options'] = User::getAsDropdown(true);
        }

        if (isset($fields['status'])) {
            $fields['status']['type']    = 'select';
            $fields['status']['options'] = static::getStatusArray(true);
        }

        return $fields;
    }

    //-----------------------------------------------------
    // Accessors
    //-----------------------------------------------------

    /**
     * Get user name attribute.
     *
     * @return string
     */
    public function getUserNameAttribute(): string {
        return $this->getRelationshipValue('rUser', 'name', $this->user_id);
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------

    /**
     * Get the user related to the API request log.
     *
     * @return BelongsTo
     */
    public function rUser(): BelongsTo {
        return $this->belongsTo(User::class, 'user_id');
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------

    /**
     * Get status array with translations for logs.
     *
     * @param  bool  $addPleaseSelect
     * @return array<int|string, string>
     */
    public static function getStatusArray(bool $addPleaseSelect = true): array {
        $status = parent::getStatusArray(false);

        if ($addPleaseSelect) {
            $status = [DomainConst::VALUE_EMPTY => __('admin::crud.please_select')] + $status;
        }

        return $status;
    }
}
