<?php

namespace Modules\Admin\Models;

use Illuminate\Support\Facades\Storage;

/**
 * Modules\Admin\Models\FileMaster
 *
 * @property int $id
 * @property string $file_name File name
 * @property string $file_path File path
 * @property int $file_type File type (1:image, 2:PDF, 3:Excel, 4:Word, ...)
 * @property int|null $file_size File size (bytes)
 * @property string $belong_type Belong table (e.g. profiles, sections, room_types)
 * @property int $belong_id Belong ID
 * @property string|null $relation_type Relation type (attachment, thumbnail, etc.)
 * @property int $display_order Display order for sorting
 * @property string|null $alt_text Alt text for images (SEO/accessibility)
 * @property string|null $title Image title/caption
 * @property int $status Status (1:active, 0:inactive)
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property int|null $created_by
 */
class FileMaster extends AdminModel {
    //-----------------------------------------------------
    // Constants
    //-----------------------------------------------------
    /** File type image */
    public const TYPE_IMAGE = 1;

    /** File type PDF */
    public const TYPE_PDF = 2;

    /** File type Excel */
    public const TYPE_EXCEL = 3;

    /** File type Word */
    public const TYPE_WORD = 4;

    /** Belong type: profile */
    public const BELONG_TYPE_PROFILE = 'profiles';

    /** Belong type: section */
    public const BELONG_TYPE_SECTION = 'sections';

    /** Belong type: room type */
    public const BELONG_TYPE_ROOM_TYPE = 'room_types';

    /** Belong type: customer */
    public const BELONG_TYPE_CUSTOMER = 'customers';

    /** Upload type: Image */
    public const UPLOAD_TYPE_IMAGE = 'image';

    /** Upload type: Document */
    public const UPLOAD_TYPE_DOCUMENT = 'document';

    /**
     * Upload configuration for image type.
     *
     * @var array<string, mixed>
     */
    public const UPLOAD_CONFIG_IMAGE = [
        'max_file_upload'     => 10,
        'max_size_upload'     => 5 * 1024 * 1024, // 5MB in bytes
        'accepted_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'accepted_mime_types' => [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
        ],
    ];

    /**
     * Upload configuration for document type.
     *
     * @var array<string, mixed>
     */
    public const UPLOAD_CONFIG_DOCUMENT = [
        'max_file_upload'     => 10,
        'max_size_upload'     => 10 * 1024 * 1024, // 10MB in bytes
        'accepted_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv', 'rtf'],
        'accepted_mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
            'application/rtf',
        ],
    ];

    //-----------------------------------------------------
    // Properties
    //-----------------------------------------------------
    /**
     * @var string
     */
    protected $table = 'file_masters';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'belong_type',
        'belong_id',
        'relation_type',
        'display_order',
        'alt_text',
        'title',
        'status',
        'created_by',
    ];

    /**
     * Datatable columns array.
     *
     * @var array<int, string>
     */
    protected $datatableColumns = [
        'id',
        'file_name',
        'file_type',
        'file_size',
        'belong_type',
        'belong_id',
        'relation_type',
        'status',
        'action',
    ];

    /**
     * Filterable columns.
     *
     * @var array<int, string>
     */
    protected $filterable = [
        'file_name',
        'file_type',
        'belong_type',
        'belong_id',
        'relation_type',
        'status',
    ];

    /**
     * Columns that use LIKE for searching.
     *
     * @var array<int, string>
     */
    protected $filterLike = [
        'file_name',
        'belong_type',
        'relation_type',
    ];

    /**
     * Filter panel columns.
     *
     * @var array<int, string>
     */
    protected $filterPanel = [
        'file_name',
        'file_type',
        'belong_type',
        'relation_type',
        'status',
    ];

    //-----------------------------------------------------
    // Override methods
    //-----------------------------------------------------
    /**
     * The "booted" method of the model.
     * Delete file from storage when FileMaster record is deleted.
     *
     * @return void
     */
    protected static function boot(): void {
        parent::boot();

        static::deleting(function ($model) {
            // Delete file from storage
            if ($model->file_path && Storage::disk('public')->exists($model->file_path)) {
                Storage::disk('public')->delete($model->file_path);
            }
        });
    }

    //-----------------------------------------------------
    // Accessors
    //-----------------------------------------------------
    /**
     * Get file URL
     *
     * @return string|null
     */
    public function getUrlAttribute(): ?string {
        if (!$this->file_path) {
            return null;
        }

        $url = Storage::disk('public')->url($this->file_path);

        // Normalize URL to prevent double slashes (except after http:// or https://)
        if ($url) {
            $url = preg_replace('#([^:])//+#', '$1/', $url);

            // Convert relative URL to absolute URL if needed
            if (!str_starts_with($url, 'http')) {
                $url = asset($url);
            }
        }

        return $url;
    }

    //-----------------------------------------------------
    // Static methods
    //-----------------------------------------------------
}
