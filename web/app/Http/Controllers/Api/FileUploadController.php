<?php

namespace App\Http\Controllers\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;
use App\Utils\FileMasterHelper;
use Modules\Admin\Models\FileMaster;
use Modules\Logging\Utils\LogHandler;

/**
 * API Controller for handling file uploads.
 */
class FileUploadController extends Controller {
    /**
     * Upload file to temporary storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function uploadTmp(Request $request): JsonResponse {
        // Debug: Log all request data to understand what FilePond sends
        LogHandler::debug('FilePond upload request', [
            'all_files'    => array_keys($request->allFiles()),
            'has_file'     => $request->hasFile('file'),
            'has_filepond' => $request->hasFile('filepond'),
            'has_files'    => $request->hasFile('files'),
            'all_input'    => array_keys($request->all()),
        ], LogHandler::CHANNEL_API);

        // FilePond sends file with the input name, but we need to check all possible field names
        // Try common field names first
        $file = null;

        // Check all possible field names (FilePond default is 'filepond')
        $possibleNames = ['filepond', 'file', 'files', 'upload', 'attachment'];
        foreach ($possibleNames as $name) {
            if ($request->hasFile($name)) {
                $file = $request->file($name);
                // If it's an array, get the first one
                if (is_array($file)) {
                    $file = reset($file);
                }
                if ($file instanceof UploadedFile) {
                    break;
                }
            }
        }

        // If still no file, get all files and use the first one
        if (!$file || !($file instanceof UploadedFile)) {
            $allFiles = $request->allFiles();
            if (!empty($allFiles)) {
                $file = reset($allFiles);
                // Handle nested arrays (e.g., files[])
                while (is_array($file) && !empty($file)) {
                    $file = reset($file);
                }
            }
        }

        // Ensure $file is an UploadedFile instance
        if (!$file || !($file instanceof UploadedFile)) {
            LogHandler::error('No file found in request', [
                'all_files'    => $request->allFiles(),
                'content_type' => $request->header('Content-Type'),
            ], LogHandler::CHANNEL_API);

            return response()->json([
                'error'   => true,
                'message' => 'File is required. Please check that file is being sent correctly.',
            ], 422);
        }

        // Validate file type first to determine max size
        $mimeType     = $file->getMimeType();
        $allowedMimes = array_merge(
            FileMaster::UPLOAD_CONFIG_IMAGE['accepted_mime_types'],
            FileMaster::UPLOAD_CONFIG_DOCUMENT['accepted_mime_types']
        );

        if (!in_array($mimeType, $allowedMimes, true)) {
            return response()->json([
                'error'   => true,
                'message' => 'File type not allowed.',
            ], 422);
        }

        // Determine max file size based on file type
        $isImage = in_array($mimeType, FileMaster::UPLOAD_CONFIG_IMAGE['accepted_mime_types'], true);
        $maxSize = $isImage
            ? FileMaster::UPLOAD_CONFIG_IMAGE['max_size_upload']
            : FileMaster::UPLOAD_CONFIG_DOCUMENT['max_size_upload'];

        // Validate file size
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / (1024 * 1024), 2);

            return response()->json([
                'error'   => true,
                'message' => "File size exceeds maximum allowed size ({$maxSizeMB}MB).",
            ], 422);
        }

        try {
            $filename = FileMasterHelper::uploadToTmp($file);

            try {
                FileMasterHelper::cleanupTmpFiles();
            } catch (Exception $e) {
                LogHandler::warning('Tmp cleanup failed after upload', [
                    'error' => $e->getMessage(),
                ], LogHandler::CHANNEL_API);
            }

            return response()->json([
                'error'    => false,
                'filename' => $filename,
                'message'  => 'File uploaded successfully.',
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to upload file to tmp', [
                'error' => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return response()->json([
                'error'   => true,
                'message' => 'Failed to upload file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove file from temporary storage.
     *
     * @param  string  $filename
     * @return JsonResponse
     */
    public function removeTmp(string $filename): JsonResponse {
        try {
            $removed = FileMasterHelper::removeFromTmp($filename);

            if ($removed) {
                return response()->json([
                    'error'   => false,
                    'message' => 'File removed successfully.',
                ]);
            }

            return response()->json([
                'error'   => true,
                'message' => 'File not found.',
            ], 404);
        } catch (Exception $e) {
            LogHandler::error('Failed to remove file from tmp', [
                'filename' => $filename,
                'error'    => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return response()->json([
                'error'   => true,
                'message' => 'Failed to remove file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove existing file from database and storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function removeExisting(int $id): JsonResponse {
        try {
            $file = FileMaster::findOrFail($id);

            // Delete file from storage
            if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }

            // Delete record
            $file->delete();

            return response()->json([
                'error'   => false,
                'message' => 'File removed successfully.',
            ]);
        } catch (Exception $e) {
            LogHandler::error('Failed to remove existing file', [
                'file_id' => $id,
                'error'   => $e->getMessage(),
            ], LogHandler::CHANNEL_API);

            return response()->json([
                'error'   => true,
                'message' => 'Failed to remove file: ' . $e->getMessage(),
            ], 500);
        }
    }
}
