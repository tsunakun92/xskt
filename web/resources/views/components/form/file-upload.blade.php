{{--
    File upload component using FilePond with server-side processing.
    Usage: <x-form.file-upload name="files" label="Images" :existing="$existingFiles" />
--}}

@props([
    'name' => '',
    'id' => '',
    'label' => '',
    'existing' => [],
    'required' => false,
    'disabled' => false,
    'hidden' => false,
    'multiple' => true,
    'accepted_extensions' => [],
    'accepted_mime_types' => [],
    'max_file_upload' => 10,
    'max_size_upload' => 5242880,
])

@php
    $id = $id ?: $name;
@endphp

<div class="@if ($hidden) hidden @endif">
    @if ($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
        </label>
    @endif

    @php
        $accept = '';
        if (!empty($accepted_extensions)) {
            $accept = '.' . implode(',.', $accepted_extensions);
        } elseif (!empty($accepted_mime_types)) {
            $accept = implode(',', $accepted_mime_types);
        }
    @endphp

    <input type="file" name="{{ $multiple ? $name . '[]' : $name }}" id="{{ $id }}"
        {{ $multiple ? 'multiple' : '' }} {{ $required ? 'required' : '' }} {{ $disabled ? 'disabled' : '' }}
        @if ($accept) accept="{{ $accept }}" @endif
        {{ $attributes->merge(['class' => 'filepond']) }} />

    <input type="hidden" name="tmp_files" id="{{ $id }}_tmp_files" value="" />
    <input type="hidden" name="deleted_files" id="{{ $id }}_deleted_files" value="" />

    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
    @error($name . '.*')
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

@push('scripts')
    <script type="module">
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById(@json($id));
            if (!input) {
                return;
            }

            function initFilePond() {
                if (typeof FilePond === 'undefined' || typeof initFilePondUpload === 'undefined') {
                    setTimeout(initFilePond, 100);
                    return;
                }

                if (typeof FilePondPluginFileValidateSize !== 'undefined') {
                    FilePond.registerPlugin(FilePondPluginFileValidateSize);
                }

                const existingFiles = @json($existing);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                initFilePondUpload(input, {
                    existingFiles: existingFiles,
                    maxFileSizeBytes: {{ $max_size_upload }},
                    maxFiles: {{ $max_file_upload }},
                    multiple: {{ $multiple ? 'true' : 'false' }},
                    uploadUrl: '{{ route('api.files.tmp.upload') }}',
                    removeUrl: '{{ route('api.files.tmp.remove', ['filename' => 'FILENAME_PLACEHOLDER']) }}',
                    csrfToken: csrfToken,
                    tmpFilesInputId: @json($id) + '_tmp_files',
                    deletedFilesInputId: @json($id) + '_deleted_files',
                });
            }

            initFilePond();
        });
    </script>
@endpush
