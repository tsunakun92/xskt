<?php

namespace Modules\XSKT\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Datatables\Models\DatatableModel;
use App\Utils\DomainConst;

/**
 * Class Draw
 *
 * Represents a lottery draw session.
 *
 * @property int $id ID of record
 * @property string $region Region (MB/MT/MN)
 * @property string|null $province_code Province code
 * @property string|null $station_code Station code
 * @property string $draw_date Draw date
 * @property \Carbon\Carbon|null $confirmed_at Confirmed at timestamp
 * @property int $status Status
 * @property int|null $created_by Created by user ID
 * @property \Carbon\Carbon $created_at Created at timestamp
 * @property \Carbon\Carbon $updated_at Updated at timestamp
 *
 * Relationships
 * @property \Illuminate\Database\Eloquent\Collection|Result[] $rResults
 */
class Draw extends XsktModel {
    use DatatableModel;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'draws';

    /**
     * Fillable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'region',
        'province_code',
        'station_code',
        'draw_date',
        'confirmed_at',
        'status',
    ];

    /**
     * Datatable columns configuration.
     *
     * @var array<int, string>
     */
    protected $datatableColumns = [
        'id',
        'region',
        'province_code',
        'station_code',
        'draw_date',
        'status',
        'created_at',
    ];

    /**
     * Filterable columns.
     *
     * @var array<int, string>
     */
    protected $filterable = [
        'region',
        'province_code',
        'station_code',
        'draw_date',
        'status',
    ];

    /**
     * Show filter panel.
     *
     * @var bool
     */
    protected $showFilterPanel = true;

    /**
     * Show filter form.
     *
     * @var bool
     */
    protected $showFilterForm = true;

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
            'region' => [
                'type'   => 'array',
                'column' => 'region',
                'values' => static::getRegionArray(false),
            ],
            'status' => [
                'type'   => 'array',
                'column' => 'status',
                'values' => static::getStatusArray(false),
            ],
        ];
    }

    //-----------------------------------------------------
    // Override methods
    //-----------------------------------------------------

    /**
     * Override form fields for Draw.
     *
     * @param  string  $routeName
     * @param  string|null  $action
     * @return array<string, array<string, mixed>>
     */
    public static function getFormFields(string $routeName, ?string $action = null): array {
        $fields = parent::getFormFields($routeName, $action);

        // Override region field
        if (isset($fields['region'])) {
            $fields['region']['type']    = 'select';
            $fields['region']['options'] = static::getRegionArray();
        }

        // Override draw_date field
        if (isset($fields['draw_date'])) {
            $fields['draw_date']['type'] = 'date';
        }

        return $fields;
    }

    /**
     * Override filter fields for Draw.
     *
     * @param  string  $routeName
     * @return array<string, array<string, mixed>>
     */
    public static function getFilterFields(string $routeName): array {
        $fields = parent::getFilterFields($routeName);

        // Override region filter
        if (isset($fields['region'])) {
            $fields['region']['type']    = 'select';
            $fields['region']['options'] = static::getRegionArray(true);
        }

        // Override status filter
        if (isset($fields['status'])) {
            $fields['status']['type']    = 'select';
            $fields['status']['options'] = static::getStatusArray(true);
        }

        // Override draw_date filter
        if (isset($fields['draw_date'])) {
            $fields['draw_date']['type'] = 'date';
        }

        return $fields;
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------

    /**
     * Get the results for this draw.
     *
     * @return HasMany
     */
    public function rResults(): HasMany {
        return $this->hasMany(Result::class, 'draw_id');
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------

    /**
     * Get region array for dropdowns.
     *
     * @param  bool  $addPleaseSelect
     * @return array<string, string>
     */
    public static function getRegionArray(bool $addPleaseSelect = true): array {
        $regions = [
            'MB' => __('xskt::crud.draws.region_mb'),
            'MT' => __('xskt::crud.draws.region_mt'),
            'MN' => __('xskt::crud.draws.region_mn'),
        ];

        if ($addPleaseSelect) {
            $regions = [DomainConst::VALUE_EMPTY => __('xskt::crud.please_select')] + $regions;
        }

        return $regions;
    }
}
