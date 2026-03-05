@props(['fields'])

@if (!empty($fields))
    <div>
        <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">@lang('datatables::datatables.filter')</h2>

        <form wire:submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            id="filterForm">
            @foreach ($fields as $name => $attributes)
                @php
                    $fieldLabel = $attributes['label'] ?? ucfirst(str_replace('_', ' ', $name));
                    $fieldType = $attributes['type'] ?? 'text';
                @endphp
                <div class="space-y-1">
                    @if (!in_array($fieldType, ['range-date', 'select-search']))
                        <label for="filter_{{ $name }}"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $fieldLabel }}
                        </label>
                    @endif

                    @switch($fieldType)
                        @case('select')
                            <select id="filter_{{ $name }}"
                                @if (!isset($attributes['livewire']) || $attributes['livewire'] === true) wire:model.defer="pendingFilters.{{ $name }}" @endif
                                wire:change="updateDependencyOnly('{{ $name }}', $event.target.value)"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                {{ $attributes['disabled'] ?? false ? 'disabled' : '' }}>
                                @if (isset($attributes['options']))
                                    @foreach ($attributes['options'] as $value => $optionLabel)
                                        <option value="{{ $value }}">{{ $optionLabel }}</option>
                                    @endforeach
                                @endif
                            </select>
                        @break

                        @case('select-search')
                            @livewire('select-search-livewire', [
                                'name' => $name,
                                'required' => $attributes['required'] ?? false,
                                'disabled' => $attributes['disabled'] ?? false,
                                'hidden' => $attributes['hidden'] ?? false,
                                'searchModel' => $attributes['search_model'] ?? null,
                                'searchColumn' => $attributes['search_column'] ?? null,
                                'searchDependsOn' => $attributes['search_depends_on'] ?? null,
                                'searchOption' => $attributes['search_option'] ?? ['id', 'name'],
                                'textDefault' => $attributes['text_default'] ?? null,
                                'textLoading' => $attributes['text_loading'] ?? null,
                                'textError' => $attributes['text_error'] ?? null,
                                'textNotFound' => $attributes['text_not_found'] ?? null,
                                'usePendingFilters' => true,
                            ])
                        @break

                        @case('date')
                            <x-form.input type="date" name="filter_{{ $name }}" id="filter_{{ $name }}"
                                wire:model.defer="pendingFilters.{{ $name }}"
                                placeholder="{{ $attributes['placeholder'] ?? '' }}"
                                disabled="{{ $attributes['disabled'] ?? false }}"
                                readonly="{{ $attributes['readonly'] ?? false }}" />
                        @break

                        @case('range-date')
                            <div class="grid grid-cols-2 gap-2 col-span-full md:col-span-1">
                                <x-form.input type="date" name="filter_{{ $name }}_from"
                                    id="filter_{{ $name }}_from" label="{{ $fieldLabel . ' (開始)' }}"
                                    wire:model.defer="pendingFilters.{{ $name }}_from"
                                    wire:change="updatePendingRangeDate('{{ $name }}')"
                                    disabled="{{ $attributes['disabled'] ?? false }}"
                                    readonly="{{ $attributes['readonly'] ?? false }}" />
                                <x-form.input type="date" name="filter_{{ $name }}_to"
                                    id="filter_{{ $name }}_to" label="{{ $fieldLabel . ' (終了)' }}"
                                    wire:model.defer="pendingFilters.{{ $name }}_to"
                                    wire:change="updatePendingRangeDate('{{ $name }}')"
                                    disabled="{{ $attributes['disabled'] ?? false }}"
                                    readonly="{{ $attributes['readonly'] ?? false }}" />
                            </div>
                            @error("pendingFilters.{$name}")
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @error("pendingFilters.{$name}_from")
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            @error("pendingFilters.{$name}_to")
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        @break

                        @default
                            <x-form.input type="{{ $fieldType }}" name="filter_{{ $name }}"
                                id="filter_{{ $name }}" wire:model.defer="pendingFilters.{{ $name }}"
                                placeholder="{{ $attributes['placeholder'] ?? '' }}"
                                disabled="{{ $attributes['disabled'] ?? false }}"
                                readonly="{{ $attributes['readonly'] ?? false }}" />
                    @endswitch
                </div>
            @endforeach
        </form>

        {{-- Submit and Reset buttons inside the form --}}
        <div class="flex justify-end mt-4 space-x-2 w-full">
            <button type="reset" wire:click="clearFilters" form="filterForm" onclick="triggerSelectSearchReset()"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-600 dark:text-white dark:border-gray-500 dark:hover:bg-gray-700">
                @lang('datatables::datatables.reset')
            </button>
            <button type="submit" form="filterForm"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                @lang('datatables::datatables.filter')
            </button>
        </div>
        <hr class="my-4">
    </div>

    <script>
        function triggerSelectSearchReset() {
            // Small delay to let Livewire clear filters first
            setTimeout(() => {
                window.dispatchEvent(new Event('resetSelectSearch'));
            }, 100);
        }
    </script>
@endif
