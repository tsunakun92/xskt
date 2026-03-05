{{--
    Example Usage:
    <x-button>Default</x-button>
    <x-button style="alternative">Alternative</x-button>
    <x-button style="dark" href="https://example.com">Dark Link</x-button>
    <x-button style="green" disabled>Disabled Green</x-button>
    <x-button style="yellow" type="submit">Yellow Submit</x-button>
    <x-button style="purple" class="custom-class">Purple with Custom Class</x-button>
--}}

@props(['type' => 'button', 'style' => 'default', 'href' => null, 'disabled' => false])

@php
    $styles = [
        'default' =>
            'me-1 mt-1 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800',
        'alternative' =>
            'me-1 mt-1 py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700',
        'dark' =>
            'me-1 mt-1 text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700',
        'light' =>
            'me-1 mt-1 text-gray-900 bg-white border border-gray-300 focus:outline-none hover:bg-gray-100 focus:ring-4 focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-gray-800 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:border-gray-600 dark:focus:ring-gray-700',
        'green' =>
            'me-1 mt-1 focus:outline-none text-white bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800',
        'red' =>
            'me-1 mt-1 focus:outline-none text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-900',
        'yellow' =>
            'me-1 mt-1 focus:outline-none text-white bg-yellow-400 hover:bg-yellow-500 focus:ring-4 focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:focus:ring-yellow-900',
        'purple' =>
            'me-1 mt-1 focus:outline-none text-white bg-purple-700 hover:bg-purple-800 focus:ring-4 focus:ring-purple-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-purple-600 dark:hover:bg-purple-700 dark:focus:ring-purple-900',
    ];

    $selectedStyle = $styles[$style] ?? $styles['default'];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $selectedStyle]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $selectedStyle, 'disabled' => $disabled]) }}>
        {{ $slot }}
    </button>
@endif
