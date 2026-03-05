{{--
    Simple Table Component

    Example Usage:
    <x-table>
        <thead>
            <tr><th>Header 1</th><th>Header 2</th></tr>
        </thead>
        <tbody>
            <tr><td>Data 1</td><td>Data 2</td></tr>
        </tbody>
    </x-table>

    With additional classes:
    <x-table table-class="table-striped" container-class="overflow-hidden">
        <thead>
            <tr><th>Header 1</th><th>Header 2</th></tr>
        </thead>
        <tbody>
            <tr><td>Data 1</td><td>Data 2</td></tr>
        </tbody>
    </x-table>
--}}

@props([
    'containerClass' => '',
    'tableClass' => '',
])

@php
    // Base classes
    $containerClasses = 'table-container';
    $tableClasses = 'table-default table-nowrap';

    // Add additional classes if provided
    if ($containerClass) {
        $containerClasses .= ' ' . $containerClass;
    }

    if ($tableClass) {
        $tableClasses .= ' ' . $tableClass;
    }
@endphp

<div class="{{ $containerClasses }} min-h-[400px]">
    <table class="{{ $tableClasses }}" data-collapsible-table>
        {{ $slot }}
    </table>
</div>
