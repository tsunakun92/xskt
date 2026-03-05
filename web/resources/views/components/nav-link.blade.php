@props(['active'])

@php
    $classes =
        $active ?? false
            ? 'inline-flex items-center p-2 border-b-2 border-blue-700 text-sm font-medium leading-5 text-blue-700 focus:outline-none focus:border-blue-700 transition duration-150 ease-in-out'
            : 'inline-flex items-center p-2 border-b-2 border-transparent text-sm font-medium leading-5 text-black hover:text-blue-500 hover:border-blue-500 focus:outline-none focus:text-blue-500 focus:border-blue-500 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
