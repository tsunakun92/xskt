{{--
    Section Component with Optional Header

    Example Usage:
    <x-section>
        Content without header
    </x-section>

    <x-section header="User Management">
        Content with header
    </x-section>

    <x-section>
        <x-slot name="header">
            <h2 class="text-xl font-semibold">Custom Header</h2>
        </x-slot>
        Content with custom header slot
    </x-section>
--}}

@props([
    'header' => null,
])

<div
    {{ $attributes->merge(['class' => 'bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg text-gray-900 dark:text-gray-100']) }}>
    @if ($header || isset($__laravel_slots['header']))
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            @if (isset($__laravel_slots['header']))
                {{ $header }}
            @else
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $header }}</h3>
            @endif
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>
</div>
