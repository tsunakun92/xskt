<?php

namespace App\Utils;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

use Modules\Admin\Models\FileMaster;

/**
 * Helper class for managing FileMaster records and file uploads.
 */
class FileMasterHelper {
    /**
     * Cleanup old temporary files under storage (local disk: storage/app/private/tmp).
     *
     * @param  int  $hours
     * @return void
     */
    public static function cleanupTmpFiles(int $hours = 24): void {
        Artisan::call('files:cleanup-tmp', [
            '--hours' => $hours,
        ]);
    }

    /**
     * Store uploaded files and create FileMaster records.
     *
     * @param  string  $belongType  Belong table name (e.g. hr_profiles, crm_sections, crm_room_types)
     * @param  int  $belongId  Belong record ID
     * @param  array<int, UploadedFile>  $uploadedFiles  Uploaded files
     * @param  string|null  $relationType  Optional relation type (attachment, thumbnail, etc.)
     * @return void
     */
    public static function storeFiles(string $belongType, int $belongId, array $uploadedFiles, ?string $relationType = null): void {
        if (empty($uploadedFiles)) {
            return;
        }

        $userId = Auth::id();

        foreach ($uploadedFiles as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $disk     = 'public';
            $subDir   = "uploads/{$belongType}/" . now()->format('Ymd');
            $path     = $file->store($subDir, $disk);
            $mimeType = (string) $file->getMimeType();

            $fileType = self::guessFileType($mimeType);

            FileMaster::create([
                'file_name'     => $file->getClientOriginalName(),
                'file_path'     => $path,
                'file_type'     => $fileType,
                'file_size'     => $file->getSize(),
                'belong_type'   => $belongType,
                'belong_id'     => $belongId,
                'relation_type' => $relationType,
                'status'        => FileMaster::STATUS_ACTIVE,
                'created_by'    => $userId,
            ]);
        }
    }

    /**
     * Build FilePond-compatible file descriptors from FileMaster collection.
     *
     * @param  Collection<int, FileMaster>  $files
     * @return array<int, array<string, mixed>>
     */
    public static function buildFilePondFiles(Collection $files): array {
        return $files->map(function (FileMaster $file): array {
            // Use FileMaster's url accessor which handles URL generation correctly
            $url = $file->url;

            // Normalize URL to prevent double slashes (except after http:// or https://)
            if ($url) {
                $url = preg_replace('#([^:])//+#', '$1/', $url);
            }

            return [
                'source'  => $url,
                'options' => [
                    'type'     => 'local',
                    'file'     => [
                        'name' => $file->file_name,
                        'size' => $file->file_size,
                        'type' => self::guessMimeFromType($file->file_type),
                    ],
                    'metadata' => [
                        'id' => $file->id,
                    ],
                ],
            ];
        })->values()->all();
    }

    /**
     * Guess FileMaster file_type from MIME type string.
     *
     * @param  string  $mimeType
     * @return int
     */
    private static function guessFileType(string $mimeType): int {
        if (str_starts_with($mimeType, 'image/')) {
            return FileMaster::TYPE_IMAGE;
        }

        if ($mimeType === 'application/pdf') {
            return FileMaster::TYPE_PDF;
        }

        if (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
            return FileMaster::TYPE_EXCEL;
        }

        if (str_contains($mimeType, 'wordprocessingml') || str_contains($mimeType, 'msword')) {
            return FileMaster::TYPE_WORD;
        }

        return FileMaster::TYPE_IMAGE;
    }

    /**
     * Guess MIME type string from FileMaster file_type.
     *
     * @param  int  $fileType
     * @return string
     */
    private static function guessMimeFromType(int $fileType): string {
        return match ($fileType) {
            FileMaster::TYPE_PDF   => 'application/pdf',
            FileMaster::TYPE_EXCEL => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            FileMaster::TYPE_WORD  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default                => 'image/*',
        };
    }

    /**
     * Guess MIME type from file extension.
     * Returns specific MIME type for images (e.g., image/jpeg, image/png) instead of image/*.
     *
     * @param  string  $filename
     * @return string|null
     */
    private static function guessMimeFromFileExtension(string $filename): ?string {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'   => 'image/png',
            'gif'   => 'image/gif',
            'webp'  => 'image/webp',
            'svg'   => 'image/svg+xml',
            'bmp'   => 'image/bmp',
            'ico'   => 'image/x-icon',
            'pdf'   => 'application/pdf',
            'xlsx', 'xls' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'docx', 'doc' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => null,
        };
    }

    /**
     * Upload file to temporary storage.
     *
     * @param  UploadedFile  $file
     * @return string Unique filename
     */
    public static function uploadToTmp(UploadedFile $file): string {
        $hash       = Str::random(16);
        $timestamp  = now()->timestamp;
        $extension  = $file->getClientOriginalExtension();
        $filename   = "{$hash}_{$timestamp}." . ($extension ?: 'tmp');

        $disk = 'local';
        $path = $file->storeAs('tmp', $filename, $disk);

        // Save metadata (original filename, size, mime type)
        $metadata = [
            'original_name' => $file->getClientOriginalName(),
            'size'          => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
        ];
        $metadataPath = "tmp/{$hash}_{$timestamp}.json";
        Storage::disk($disk)->put($metadataPath, json_encode($metadata));

        return $filename;
    }

    /**
     * Remove file from temporary storage.
     *
     * @param  string  $filename
     * @return bool
     */
    public static function removeFromTmp(string $filename): bool {
        $disk = 'local';
        $path = "tmp/{$filename}";

        $deleted = false;
        if (Storage::disk($disk)->exists($path)) {
            $deleted = Storage::disk($disk)->delete($path);
        }

        // Also delete metadata file if exists
        $metadataPath = self::getMetadataPath($filename);
        if (Storage::disk($disk)->exists($metadataPath)) {
            Storage::disk($disk)->delete($metadataPath);
        }

        return $deleted;
    }

    /**
     * Get metadata file path for a tmp filename.
     *
     * @param  string  $filename
     * @return string
     */
    public static function getMetadataPath(string $filename): string {
        $parts = explode('.', $filename);
        $base  = implode('.', array_slice($parts, 0, -1));
        if (empty($base)) {
            $base = $filename;
        }

        return "tmp/{$base}.json";
    }

    /**
     * Move file from temporary storage to permanent storage and create FileMaster record.
     *
     * @param  string  $tmpFilename  Temporary filename
     * @param  string  $belongType  Belong table name (e.g. hr_profiles, crm_sections)
     * @param  int  $belongId  Belong record ID
     * @param  string|null  $relationType  Optional relation type (attachment, thumbnail, etc.)
     * @return FileMaster
     */
    public static function moveFromTmpToPermanent(string $tmpFilename, string $belongType, int $belongId, ?string $relationType = null): FileMaster {
        $tmpDisk = 'local';
        $tmpPath = "tmp/{$tmpFilename}";

        if (!Storage::disk($tmpDisk)->exists($tmpPath)) {
            throw new RuntimeException("Temporary file not found: {$tmpFilename}");
        }

        // Load metadata if exists
        $metadataPath = self::getMetadataPath($tmpFilename);
        $metadata     = [];
        if (Storage::disk($tmpDisk)->exists($metadataPath)) {
            $metadataContent = Storage::disk($tmpDisk)->get($metadataPath);
            $metadata        = json_decode($metadataContent, true) ?: [];
        }

        // Get file info
        $tmpFullPath  = Storage::disk($tmpDisk)->path($tmpPath);
        $originalName = $metadata['original_name'] ?? self::extractOriginalNameFromTmpFilename($tmpFilename);
        $fileSize     = $metadata['size'] ?? filesize($tmpFullPath);
        $mimeType     = $metadata['mime_type'] ?? mime_content_type($tmpFullPath);

        // Move to permanent storage
        $permanentDisk = 'public';
        $subDir        = "uploads/{$belongType}/" . now()->format('Ymd');

        // Read file content and store to permanent disk
        $fileContent   = Storage::disk($tmpDisk)->get($tmpPath);
        $permanentPath = $subDir . '/' . basename($tmpFilename);
        Storage::disk($permanentDisk)->put($permanentPath, $fileContent);

        // Delete tmp file and metadata
        $tmpDeleted      = Storage::disk($tmpDisk)->delete($tmpPath);
        $metadataDeleted = false;
        if (Storage::disk($tmpDisk)->exists($metadataPath)) {
            $metadataDeleted = Storage::disk($tmpDisk)->delete($metadataPath);
        }

        // Log deletion for debugging
        \Modules\Logging\Utils\LogHandler::debug('Moved file from tmp to permanent and deleted tmp', [
            'tmp_filename'     => $tmpFilename,
            'tmp_path'         => $tmpPath,
            'permanent_path'   => $permanentPath,
            'tmp_deleted'      => $tmpDeleted,
            'metadata_deleted' => $metadataDeleted,
            'belong_type'      => $belongType,
            'belong_id'        => $belongId,
        ]);

        // Create FileMaster record
        $userId   = Auth::id();
        $fileType = self::guessFileType($mimeType);

        return FileMaster::create([
            'file_name'     => $originalName,
            'file_path'     => $permanentPath,
            'file_type'     => $fileType,
            'file_size'     => $fileSize,
            'belong_type'   => $belongType,
            'belong_id'     => $belongId,
            'relation_type' => $relationType,
            'status'        => FileMaster::STATUS_ACTIVE,
            'created_by'    => $userId,
        ]);
    }

    /**
     * Move multiple files from temporary storage to permanent storage.
     *
     * @param  array<int, string>  $tmpFilenames  Array of temporary filenames
     * @param  string  $belongType  Belong table name
     * @param  int  $belongId  Belong record ID
     * @param  string|null  $relationType  Optional relation type
     * @return void
     */
    public static function moveMultipleFromTmp(array $tmpFilenames, string $belongType, int $belongId, ?string $relationType = null): void {
        if (empty($tmpFilenames)) {
            \Modules\Logging\Utils\LogHandler::debug('moveMultipleFromTmp: No tmp files to move');

            return;
        }

        \Modules\Logging\Utils\LogHandler::info('Moving multiple files from tmp to permanent', [
            'tmp_filenames' => $tmpFilenames,
            'belong_type'   => $belongType,
            'belong_id'     => $belongId,
            'count'         => count($tmpFilenames),
        ]);

        foreach ($tmpFilenames as $tmpFilename) {
            if (empty($tmpFilename)) {
                continue;
            }

            try {
                self::moveFromTmpToPermanent($tmpFilename, $belongType, $belongId, $relationType);
            } catch (Exception $e) {
                \Modules\Logging\Utils\LogHandler::error('Failed to move tmp file to permanent', [
                    'tmp_filename' => $tmpFilename,
                    'error'        => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        \Modules\Logging\Utils\LogHandler::info('Successfully moved all tmp files to permanent', [
            'count' => count($tmpFilenames),
        ]);
    }

    /**
     * Build FilePond-compatible file descriptors for edit mode.
     * Includes metadata for removing existing files.
     *
     * @param  Collection<int, FileMaster>  $files
     * @return array<int, array<string, mixed>>
     */
    public static function buildFilePondFilesForEdit(Collection $files): array {
        return $files->map(function (FileMaster $file): array {
            // Use FileMaster's url accessor which handles URL generation correctly
            $url = $file->url;

            // Normalize URL to prevent double slashes
            if ($url) {
                // Remove any double slashes (except after http:// or https://)
                $url = preg_replace('#([^:])//+#', '$1/', $url);
            }

            // Guess MIME type from file extension for better FilePond image preview support
            $mimeType = self::guessMimeFromFileExtension($file->file_name);
            if (!$mimeType || $mimeType === 'image/*') {
                // Fallback to file_type if extension guess fails
                $mimeType = self::guessMimeFromType($file->file_type);
            }

            return [
                'source'  => $url,
                'options' => [
                    'type'     => 'local',
                    'file'     => [
                        'name' => $file->file_name,
                        'size' => $file->file_size,
                        'type' => $mimeType,
                    ],
                    'metadata' => [
                        'id'       => $file->id,
                        'serverId' => $file->id,
                    ],
                ],
            ];
        })->values()->all();
    }

    /**
     * Extract original filename from temporary filename.
     * Fallback when metadata is not available.
     * Format: {hash}_{timestamp}.{ext}
     *
     * @param  string  $tmpFilename
     * @return string
     */
    private static function extractOriginalNameFromTmpFilename(string $tmpFilename): string {
        // Try to extract extension
        $parts = explode('.', $tmpFilename);
        if (count($parts) > 1) {
            $ext = end($parts);

            return 'uploaded_file.' . $ext;
        }

        return 'uploaded_file';
    }
}
