@props([
    'title' => null,
    'open' => false,
    'name' => null,
])

@php
    $isOpen = filter_var($open, FILTER_VALIDATE_BOOLEAN);
@endphp

<div x-data="{ open: {{ $isOpen ? 'true' : 'false' }} }"
    {{ $attributes->merge(['class' => 'border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800']) }}>
    <button type="button"
        @click="
            open = !open;
            @if ($name) $wire.setFilterSectionState('{{ $name }}', open); @endif
        "
        class="w-full px-4 py-3 flex items-center justify-between text-sm font-semibold text-gray-800 dark:text-gray-200">
        <span>
            @if (isset($__laravel_slots['header']))
                {{ $header }}
            @else
                {{ $title }}
            @endif
        </span>
        <span class="ml-2 text-gray-500 dark:text-gray-400">
            <i class="fas" :class="open ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
        </span>
    </button>

    <div class="px-4 pb-4 pt-1" x-show="open" x-transition>
        {{ $slot }}
    </div>
</div>
