<div>
    @if ($showFilterForm && !empty($filterFields))
        @if (!empty($extraFilterForm))
            @include($extraFilterForm, ['fields' => $filterFields])
        @else
            @include('datatables::components.datatables.filter-form', ['fields' => $filterFields])
        @endif
    @endif

    <div class="mb-4 flex justify-between items-center text-sm text-gray-600">
        <div class="flex items-center">
            <label for="perPage" class="text-sm text-gray-600">@lang('datatables::datatables.per_page'):</label>
            <select wire:model.live="perPage" id="perPage"
                class="border border-gray-300 rounded-md px-2 py-1 text-sm ml-2">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </div>

        @if ($showFilterPanel)
            <button type="button" wire:click="clearFilters"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700">
                @lang('datatables::datatables.clear_filters')
            </button>
        @endif
    </div>

    {{-- Collapsible Toggles --}}
    @if (!empty($groupColumns))
        <div class="collapsible-toggles my-2 text-right" wire:ignore>
            @foreach ($groupColumns as $groupName => $groupCols)
                <button type="button"
                    class="px-3 py-1 text-sm font-medium border rounded-md shadow-sm ml-2 focus:outline-none collapsible-toggle-btn"
                    data-group="{{ $groupName }}">
                    <span class="collapsible-icon"><i class="fas fa-minus mr-1"></i></span>
                    <span class="collapsible-text">{{ __('datatables::datatables.hide') }}</span>
                    <span class="collapsible-group-name ml-1">{{ $groupName }}</span>
                </button>
            @endforeach
        </div>
    @endif

    {{-- Data Table --}}
    <div data-datatables-root data-table-key="datatable:{{ $routeName }}" data-hide-until-init="1"
        style="visibility:hidden">
        <x-table container-class="min-h-[400px]">
            <thead class="text-xs text-gray-700 bg-gray-100 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    @foreach ($columns as $column => $label)
                        @php
                            $groupName = null;
                            foreach ($groupColumns as $groupKey => $groupCols) {
                                if (in_array($column, $groupCols)) {
                                    $groupName = $groupKey;
                                    break;
                                }
                            }
                            $isSorted = $sortBy === $column;
                            $isFiltered = !empty($filters[$column . '_filter'] ?? []);
                            $isSortable = in_array($column, $sortableColumns);
                            $isFilterable =
                                $showFilterPanel &&
                                (array_key_exists($column, $filterFields) ||
                                    in_array($column, $this->modelClass::getFilterPanelArray()));

                            $triggerClasses =
                                'excel-dropdown-trigger ml-2 p-1 rounded transition-colors duration-150 ' .
                                match (true) {
                                    $isSorted && $isFiltered => 'bg-purple-100 text-purple-700 hover:bg-purple-200',
                                    $isSorted => 'bg-blue-100 text-blue-700 hover:bg-blue-200',
                                    $isFiltered => 'bg-green-100 text-green-700 hover:bg-green-200',
                                    default => 'hover:bg-gray-100 text-gray-400 hover:text-gray-600',
                                };
                        @endphp
                        <th scope="col"
                            class="p-3 {{ $loop->first ? 'rounded-s-lg' : '' }} {{ $column === 'action' ? 'w-32 text-center' : '' }} {{ $loop->last ? 'rounded-e-lg' : '' }} relative"
                            @if ($groupName) data-collapsible-group="{{ $groupName }}" @endif>
                            <div class="flex items-center justify-between">
                                <span>{{ $label }}</span>
                                @if ($isSortable || $isFilterable)
                                    <button type="button" class="{{ $triggerClasses }}"
                                        data-column="{{ $column }}" data-label="{{ $label }}">
                                        <div class="flex items-center">
                                            {{-- Sort direction indicator --}}
                                            @if ($isSorted)
                                                @if ($sortDirection === 'asc')
                                                    <i class="fas fa-chevron-up w-3 h-3"></i>
                                                @else
                                                    <i class="fas fa-chevron-down w-3 h-3"></i>
                                                @endif
                                            @endif

                                            {{-- Filter indicator --}}
                                            @if ($isFiltered)
                                                <i
                                                    class="fas fa-filter w-3 h-3 @if ($isSorted) ml-1 @endif"></i>
                                            @endif

                                            {{-- Default dropdown arrow if no active states --}}
                                            @if (!$isSorted && !$isFiltered)
                                                <i class="fas fa-chevron-down w-3 h-3"></i>
                                            @endif
                                        </div>
                                    </button>
                                @endif
                            </div>
                            @if ($isSortable || $isFilterable)
                                @include('datatables::components.datatables.filter-panel', [
                                    'column' => $column,
                                    'label' => $label,
                                    'sortableColumns' => $sortableColumns,
                                    'filterFields' => $showFilterPanel ? $filterFields : [],
                                    'filterPanelColumns' => $showFilterPanel
                                        ? $this->modelClass::getFilterPanelArray()
                                        : [],
                                    'sortBy' => $sortBy,
                                    'sortDirection' => $sortDirection,
                                ])
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if ($this->data->isEmpty())
                    <tr>
                        <td colspan="{{ count($columns) }}" class="px-6 py-4 text-center text-gray-500">
                            {{ $emptyMessage }}
                        </td>
                    </tr>
                @else
                    @php
                        $index = 1;
                    @endphp
                    @foreach ($this->data as $item)
                        @php
                            $rowKey = null;
                            if (is_object($item) && method_exists($item, 'getKey')) {
                                $rowKey = $item->getKey();
                            }
                            $rowKey ??= get_value($item, 'id', null);
                            $rowKey ??= $loop->index;
                        @endphp
                        <tr wire:key="dt-row-{{ $routeName }}-{{ $rowKey }}"
                            class="bg-white dark:bg-gray-800 {{ stringify_value(get_value($item, '__row_class', ''), '') }}">
                            @foreach ($columns as $column => $label)
                                <td
                                    class="p-3 {{ $column === 'action' ? 'text-center' : '' }} {{ stringify_value(get_value($item, $column . '_class', ''), '') }}">
                                    @switch($column)
                                        @case('no')
                                            <span
                                                class="{{ stringify_value(get_value($item, 'no_class', ''), '') }}">{{ $index++ }}</span>
                                        @break

                                        @case('action')
                                            <div class="flex justify-center space-x-2">
                                                @include('datatables::components.datatables.actions', [
                                                    'row' => $item,
                                                    'route' => $routeName,
                                                    'extraActions' => $extraActions ?? [],
                                                    'defaultActions' => $defaultActions ?? true,
                                                ])
                                            </div>
                                        @break

                                        @case('status')
                                            <x-badge style="{{ $item->status ? 'green' : 'red' }}">
                                                {{ $item->getStatusName() }}
                                            </x-badge>
                                        @break

                                        @case('pdf')
                                            @if ($item->pdf)
                                                <a class="text-blue-500" href="{{ route('preview-pdf', $item) }}"
                                                    target="_blank">
                                                    {{ __('datatables::datatables.preview_pdf') }}
                                                </a>
                                            @else
                                                {{ __('datatables::datatables.no_pdf') }}
                                            @endif
                                        @break

                                        @default
                                            @if ($this->hasCustomRenderer($column))
                                                @php
                                                    $customRenderer = $this->getCustomRenderer($column);
                                                    $customData = $this->getCustomColumnData();
                                                @endphp
                                                @include($customRenderer, [
                                                    'value' => get_value($item, $column),
                                                    'row' => $item,
                                                    'column' => $column,
                                                    'index' => $index - 1,
                                                    ...$customData,
                                                ])
                                            @else
                                                {{ stringify_value(get_value($item, $column), '-') }}
                                            @endif
                                    @endswitch
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endif
            </tbody>
        </x-table>

        @if ($this->data->hasPages())
            <div class="mt-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div class="mb-4 sm:mb-0">
                        <p class="text-sm text-gray-700">
                            @lang('datatables::datatables.showing') <span class="font-medium">{{ $this->data->firstItem() ?: 0 }}</span>
                            @lang('datatables::datatables.to') <span class="font-medium">{{ $this->data->lastItem() ?: 0 }}</span>
                            @lang('datatables::datatables.of') <span class="font-medium">{{ $this->data->total() }}</span>
                            @lang('datatables::datatables.results')
                        </p>
                    </div>

                    <nav aria-label="@lang('datatables::datatables.pagination_navigation')" class="flex-1 flex justify-between sm:justify-end">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start" role="group"
                            aria-label="@lang('datatables::datatables.page_navigation')">
                            @if ($this->data->onFirstPage())
                                <span
                                    class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-not-allowed rounded-l-md">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left w-5 h-5"></i>
                                </span>
                            @else
                                <button wire:click="previousPage"
                                    class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left w-5 h-5"></i>
                                </button>
                            @endif

                            {{-- Page Numbers with Ellipsis Support --}}
                            @foreach ($this->getPaginationStructure() as $item)
                                @if ($item['type'] === 'page')
                                    @if ($item['active'])
                                        <span
                                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-indigo-600 cursor-default"
                                            aria-current="page" aria-label="@lang('datatables::datatables.current_page', ['page' => $item['number']])">
                                            {{ $item['number'] }}
                                        </span>
                                    @else
                                        <button wire:click="setPage({{ $item['number'] }})"
                                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                            aria-label="@lang('datatables::datatables.go_to_page', ['page' => $item['number']])">
                                            {{ $item['number'] }}
                                        </button>
                                    @endif
                                @elseif ($item['type'] === 'ellipsis')
                                    <span
                                        class="relative inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 cursor-default"
                                        aria-hidden="true" title="@lang('datatables::datatables.more_pages')">
                                        {{ $item['label'] }}
                                    </span>
                                @endif
                            @endforeach

                            @if ($this->data->hasMorePages())
                                <button wire:click="nextPage"
                                    class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 focus:z-10 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right w-5 h-5"></i>
                                </button>
                            @else
                                <span
                                    class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 cursor-not-allowed rounded-r-md">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right w-5 h-5"></i>
                                </span>
                            @endif
                        </div>
                    </nav>
                </div>
            </div>
        @endif


        {{-- Modal Delete --}}
        @include('datatables::components.datatables.modal-delete')

        {{-- Modal Confirm Action (generic) --}}
        <x-modal.confirm-action-modal />

        {{-- Modal Cancel / Approve / Reject Cancel Booking (CRM staff/admin) --}}
        <x-modal.cancel-booking-modal />
        <x-modal.approve-cancel-modal />
        <x-modal.reject-cancel-modal />

        {{-- Include JavaScript --}}
        @include('datatables::components.datatables.script')
    </div>
</div>
