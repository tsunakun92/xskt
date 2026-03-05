{{--
    Example Usage:
    <x-badge>Default</x-badge>
    <x-badge style="dark">Dark</x-badge>
    <x-badge style="red">Red</x-badge>
    <x-badge style="green">Green</x-badge>
    <x-badge style="yellow">Yellow</x-badge>
    <x-badge style="indigo">Indigo</x-badge>
    <x-badge style="purple">Purple</x-badge>
    <x-badge style="pink">Pink</x-badge>
--}}

@props(['style' => 'default'])

@php
    $styles = [
        'default' =>
            'bg-blue-100 text-blue-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300',
        'dark' =>
            'bg-gray-100 text-gray-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded dark:bg-gray-700 dark:text-gray-300',
        'red' =>
            'bg-red-100 text-red-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300',
        'green' =>
            'bg-green-100 text-green-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300',
        'yellow' =>
            'bg-yellow-100 text-yellow-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300',
        'indigo' =>
            'bg-indigo-100 text-indigo-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded dark:bg-indigo-900 dark:text-indigo-300',
        'purple' =>
            'bg-purple-100 text-purple-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded dark:bg-purple-900 dark:text-purple-300',
        'pink' =>
            'bg-pink-100 text-pink-800 text-sm font-medium me-2 px-2.5 py-0.5 rounded dark:bg-pink-900 dark:text-pink-300',
    ];

    $selectedStyle = $styles[$style] ?? $styles['default'];
@endphp

<span {{ $attributes->merge(['class' => $selectedStyle]) }}>
    {{ $slot }}
</span>
