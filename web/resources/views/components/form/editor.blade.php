{{--
    Form Editor Component with Quill.js Integration

    Example Usage:
    <x-form.editor name="content" label="Content" />
    <x-form.editor name="description" label="Description" :value="$model->description" />
--}}

@props([
    'name' => '',
    'id' => '',
    'label' => '',
    'value' => '',
    'required' => false,
    'readonly' => false,
    'disabled' => false,
    'hidden' => false,
    'height' => '400px',
])

@php
    // Set default id if not provided
    $id = $id ?: $name;
    $editorId = $id . '_quill';
    $editorName = $name;
    $hiddenInputId = $id . '_hidden';
@endphp

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <style>
        .ql-html::before {
            content: 'HTML';
        }

        .ql-html {
            font-size: 12px;
            font-weight: bold;
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
@endpush

@include('components.form.editor-script')

<div class="@if ($hidden) hidden @endif">
    @if ($label)
        <label for="{{ $editorId }}"
            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ $label }}</label>
    @endif

    {{-- Hidden input for form submission --}}
    <input type="hidden" name="{{ $editorName }}" id="{{ $hiddenInputId }}" value="{{ old($name) ?? $value }}"
        {{ $required ? 'required' : '' }} />

    {{-- Quill editor container --}}
    <div class="quill-editor-container" data-editor-id="{{ $editorId }}" data-hidden-input-id="{{ $hiddenInputId }}"
        data-height="{{ $height }}" data-readonly="{{ $readonly ? 'true' : 'false' }}"
        data-disabled="{{ $disabled ? 'true' : 'false' }}">
        <div id="{{ $editorId }}" style="height: {{ $height }};"></div>
        <textarea id="{{ $editorId }}_html"
            class="hidden w-full font-mono text-sm p-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 rounded"
            style="height: {{ $height }}; min-height: 200px;" placeholder="HTML Source Code"></textarea>
    </div>

    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
