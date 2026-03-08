<?php

namespace Modules\XSKT\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Datatables\Models\DatatableModel;
use App\Utils\DomainConst;

/**
 * Class Result
 *
 * Represents a lottery result entry for a specific draw.
 *
 * @property int $id ID of record
 * @property int $draw_id Draw ID
 * @property string $prize_code Prize code
 * @property int $index_in_prize Index position within prize
 * @property string $number Result number
 * @property bool $confirmed_by_rule Confirmed by rule
 * @property int $status Status
 * @property int|null $created_by Created by user ID
 * @property \Carbon\Carbon $created_at Created at timestamp
 * @property \Carbon\Carbon $updated_at Updated at timestamp
 *
 * Accessors
 * @property string $draw_info
 *
 * Relationships
 * @property Draw $rDraw
 */
class Result extends XsktModel {
    use DatatableModel;

    /**
     * Table name.
     *
     * @var string
     */
    protected $table = 'results';

    /**
     * Fillable attributes.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'draw_id',
        'prize_code',
        'index_in_prize',
        'number',
        'confirmed_by_rule',
        'status',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'draw_info',
    ];

    /**
     * Datatable columns configuration.
     *
     * @var array<int, string>
     */
    protected $datatableColumns = [
        'id',
        'draw_info',
        'prize_code',
        'index_in_prize',
        'number',
        'confirmed_by_rule',
        'status',
        'created_at',
    ];

    /**
     * Filterable columns.
     *
     * @var array<int, string>
     */
    protected $filterable = [
        'draw_id',
        'prize_code',
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
     * Override form fields for Result.
     *
     * @param  string  $routeName
     * @param  string|null  $action
     * @return array<string, array<string, mixed>>
     */
    public static function getFormFields(string $routeName, ?string $action = null): array {
        $fields = parent::getFormFields($routeName, $action);

        // Override confirmed_by_rule field
        if (isset($fields['confirmed_by_rule'])) {
            $fields['confirmed_by_rule']['type']    = 'select';
            $fields['confirmed_by_rule']['options'] = [
                DomainConst::VALUE_EMPTY => __('xskt::crud.please_select'),
                '0'                      => __('xskt::crud.results.not_confirmed'),
                '1'                      => __('xskt::crud.results.confirmed'),
            ];
        }

        return $fields;
    }

    /**
     * Override filter fields for Result.
     *
     * @param  string  $routeName
     * @return array<string, array<string, mixed>>
     */
    public static function getFilterFields(string $routeName): array {
        $fields = parent::getFilterFields($routeName);

        // Override status filter
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
     * Get draw info attribute.
     *
     * @return string
     */
    public function getDrawInfoAttribute(): string {
        return $this->getRelationshipValue('rDraw', 'draw_date', $this->draw_id);
    }

    //-----------------------------------------------------
    // Declare relations
    //-----------------------------------------------------

    /**
     * Get the draw that owns this result.
     *
     * @return BelongsTo
     */
    public function rDraw(): BelongsTo {
        return $this->belongsTo(Draw::class, 'draw_id');
    }
}
