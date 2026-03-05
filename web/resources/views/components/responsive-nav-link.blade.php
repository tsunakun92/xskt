@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-indigo-400 dark:border-indigo-600 text-start text-base font-medium text-white focus:outline-none focus:border-indigo-700  ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-gray-700 dark:text-gray-400 hover:text-gray-300 dark:hover:text-gray-200 hover:border-gray-300 dark:hover:border-gray-500 focus:outline-none focus:text-gray-300 dark:focus:text-gray-200 focus:border-gray-300 dark:focus:border-gray-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
