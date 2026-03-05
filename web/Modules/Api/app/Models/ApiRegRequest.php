<?php

namespace Modules\Api\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Datatables\Models\DatatableModel;
use App\Utils\DomainConst;

/**
 * Class ApiRegRequest
 *
 * @property int $id ID of record
 * @property string $email Email
 * @property string $password Password
 * @property int $status Status
 * @property \Carbon\Carbon $created_at Created at timestamp
 * @property \Carbon\Carbon $updated_at Updated at timestamp
 */
class ApiRegRequest extends ApiModel {
    use DatatableModel, HasFactory;

    const STATUS_REGISTER_REQUEST = 2;

    protected $table = 'api_reg_requests';

    protected $fillable = [
        'email',
        'password',
        'status',
    ];

    // --- DataTable Configuration ---

    protected $datatableColumns = [
        'id',
        'email',
        'status',
        'created_at',
    ];

    protected $filterable = [
        'email',
        'status',
    ];

    protected $showFilterPanel = true;

    protected $showFilterForm = true;

    /**
     * Get datatable table columns.
     *
     * @return array
     */
    public static function getDatatableTableColumns(): array {
        return self::getBaseDatatableTableColumns();
    }

    /**
     * Get filter column mapping.
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
}
