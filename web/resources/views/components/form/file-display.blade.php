{{--
    File display component for show pages.
    Displays image previews for images and file names with download links for documents.
    Usage: <x-form.file-display :files="$existingFiles" label="Attachments" />
--}}

@props([
    'files' => [],
    'label' => '',
    'uploadType' => 'auto', // 'image' or 'document' or 'auto' (auto-detect from mime type)
])

@php
    // Helper function to check if file is an image
    $isImage = function ($file) {
        $mimeType = $file['options']['file']['type'] ?? '';
        return str_starts_with($mimeType, 'image/');
    };

    // Helper function to get file URL
    $getFileUrl = function ($file) {
        return $file['source'] ?? '';
    };

    // Helper function to get file name
    $getFileName = function ($file) {
        return $file['options']['file']['name'] ?? basename($file['source'] ?? '');
    };

    // Helper function to get file size
    $getFileSize = function ($file) {
        $size = $file['options']['file']['size'] ?? 0;
        if ($size === 0) {
            return '';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        return number_format($size, $unitIndex > 0 ? 2 : 0) . ' ' . $units[$unitIndex];
    };

    // Separate files into images and documents
    $imageFiles = [];
    $documentFiles = [];

    foreach ($files as $file) {
        if ($uploadType === 'auto') {
            if ($isImage($file)) {
                $imageFiles[] = $file;
            } else {
                $documentFiles[] = $file;
            }
        } elseif ($uploadType === 'image') {
            $imageFiles[] = $file;
        } else {
            $documentFiles[] = $file;
        }
    }
@endphp

@if (count($files) > 0)
    <div class="mb-6">
        @if ($label)
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">
                {{ $label }}
            </h3>
        @endif

        {{-- Image Preview Section --}}
        @if (count($imageFiles) > 0)
            <div class="mb-4">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($imageFiles as $file)
                        @php
                            $fileUrl = $getFileUrl($file);
                            $fileName = $getFileName($file);
                            $fileSize = $getFileSize($file);
                        @endphp
                        <div class="relative group">
                            <a href="{{ $fileUrl }}" target="_blank"
                                class="block aspect-square rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                                <img src="{{ $fileUrl }}" alt="{{ $fileName }}"
                                    class="w-full h-full object-cover" loading="lazy">
                            </a>
                            @if ($fileName || $fileSize)
                                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 truncate">
                                    @if ($fileName)
                                        <div class="font-medium truncate" title="{{ $fileName }}">
                                            {{ $fileName }}
                                        </div>
                                    @endif
                                    @if ($fileSize)
                                        <div class="text-gray-500 dark:text-gray-500">
                                            {{ $fileSize }}
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Document Files Section --}}
        @if (count($documentFiles) > 0)
            <div class="space-y-2">
                @foreach ($documentFiles as $file)
                    @php
                        $fileUrl = $getFileUrl($file);
                        $fileName = $getFileName($file);
                        $fileSize = $getFileSize($file);
                    @endphp
                    <div
                        class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center space-x-3 flex-1 min-w-0">
                            {{-- File Icon --}}
                            <div class="flex-shrink-0">
                                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>

                            {{-- File Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate"
                                    title="{{ $fileName }}">
                                    {{ $fileName }}
                                </div>
                                @if ($fileSize)
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $fileSize }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Download Button --}}
                        <div class="flex-shrink-0 ml-4">
                            <a href="{{ $fileUrl }}" download="{{ $fileName }}" target="_blank"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                {{ __('Download') }}
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
