@php

use Modules\Admin\Models\Setting;

    $isHtmlContent = in_array($row->key, Setting::EDITOR_KEYS, true);
    $modalName = 'setting-value-modal-' . $row->id;
@endphp

@if ($isHtmlContent)
    <button type="button" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 underline"
        x-on:click="$dispatch('open-modal', '{{ $modalName }}')">
        {{ __('admin::crud.settings.html_content') }}
    </button>

    {{-- Modal for HTML Content --}}
    <x-modal name="{{ $modalName }}" maxWidth="2xl">
        <x-slot name="header">
            {{ __('admin::crud.settings.setting_value') }}: {{ $row->key }}
        </x-slot>

        <div class="p-6">
            <div class="prose prose-lg dark:prose-invert max-w-none max-h-96 overflow-y-auto">
                {!! $value !!}
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-end">
            <button type="button"
                class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-600 dark:text-gray-200 dark:border-gray-500 dark:hover:bg-gray-500"
                x-on:click="$dispatch('close-modal', '{{ $modalName }}')">
                {{ __('admin::crud.settings.close') }}
            </button>
        </div>
    </x-modal>
@else
    {{-- Regular value display for non-HTML content --}}
    <div class="max-w-xs truncate" title="{{ $value }}">
        {{ strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value }}
    </div>
@endif
