@props([
    'column',
    'label',
    'sortableColumns' => [],
    'filterFields' => [],
    'filterPanelColumns' => [],
    'sortBy' => '',
    'sortDirection' => 'asc',
])

@php
    $isSortable = in_array($column, $sortableColumns);
    $isFilterable = array_key_exists($column, $filterFields) || in_array($column, $filterPanelColumns);
    $showSeparator = $isSortable && $isFilterable;
@endphp

{{-- Excel-style dropdown panel --}}
<div class="excel-dropdown-panel absolute top-full left-0 mt-1 w-64 bg-white border border-gray-200 rounded-md shadow-lg z-50 hidden"
    data-column="{{ $column }}" wire:ignore.self>
    <div class="p-3">
        {{-- Sort Section - Only show if column is sortable --}}
        @if ($isSortable)
            <div class="@if ($showSeparator) border-b border-gray-100 pb-3 mb-3 @endif">
                <button type="button"
                    class="sort-option w-full text-left px-2 py-2 hover:bg-gray-50 rounded flex items-center focus:outline-none focus:ring-0 focus:bg-gray-50"
                    data-column="{{ $column }}" data-direction="asc">
                    <i class="fas fa-sort-alpha-down w-4 h-4 mr-2 text-gray-500"></i>
                    @lang('datatables::datatables.table_filter.sort_a_to_z')
                    @if ($sortBy === $column && $sortDirection === 'asc')
                        <i class="fas fa-check w-4 h-4 ml-auto text-blue-500"></i>
                    @endif
                </button>
                <button type="button"
                    class="sort-option w-full text-left px-2 py-2 hover:bg-gray-50 rounded flex items-center focus:outline-none focus:ring-0 focus:bg-gray-50"
                    data-column="{{ $column }}" data-direction="desc">
                    <i class="fas fa-sort-alpha-up w-4 h-4 mr-2 text-gray-500"></i>
                    @lang('datatables::datatables.table_filter.sort_z_to_a')
                    @if ($sortBy === $column && $sortDirection === 'desc')
                        <i class="fas fa-check w-4 h-4 ml-auto text-blue-500"></i>
                    @endif
                </button>
            </div>
        @endif

        {{-- Filter Section - Only show if column is filterable --}}
        @if ($isFilterable)
            <div>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">@lang('datatables::datatables.table_filter.filter')</span>
                    <div class="flex space-x-2">
                        <button type="button"
                            class="filter-select-all text-xs text-blue-600 hover:text-blue-800 focus:outline-none focus:ring-0"
                            data-column="{{ $column }}">
                            @lang('datatables::datatables.table_filter.select_all')
                        </button>
                        <button type="button"
                            class="filter-deselect-all text-xs text-blue-600 hover:text-blue-800 focus:outline-none focus:ring-0"
                            data-column="{{ $column }}">
                            @lang('datatables::datatables.table_filter.deselect_all')
                        </button>
                    </div>
                </div>
                <div class="filter-values-container border border-gray-200 rounded" data-column="{{ $column }}"
                    style="height: 300px; overflow-y: auto;">
                    {{-- Filter values will be populated by JavaScript --}}
                </div>
                <div class="mt-3 flex justify-end space-x-2">
                    <button type="button"
                        class="filter-cancel px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded focus:outline-none focus:ring-0">
                        @lang('datatables::datatables.table_filter.cancel')
                    </button>
                    <button type="button"
                        class="filter-apply px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 focus:outline-none focus:ring-0 focus:bg-blue-700"
                        data-column="{{ $column }}">
                        @lang('datatables::datatables.table_filter.apply')
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
