@props(['fields'])

<x-section class="mb-6">
    <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-200">
        @lang('admin::crud.filter')
    </h2>

    <form id="filterForm" method="GET">
        <div>
            <div class="grid grid-cols-3 gap-4">
                @foreach ($fields as $name => $attributes)
                    @php
                        $value = request($name, $attributes['value'] ?? '');
                    @endphp

                    @switch($attributes['type'])
                        @case('select')
                            <x-select :name="$name" :label="$attributes['label']" :options="$attributes['options']" :selected="$value" :required="$attributes['required'] ?? false"
                                :disabled="$attributes['disabled'] ?? false" :hidden="$attributes['hidden'] ?? false" />
                        @break

                        @default
                            <x-input :type="$attributes['type']" :name="$name" :label="$attributes['label']" :value="$value" :required="$attributes['required'] ?? false"
                                :readonly="$attributes['readonly'] ?? false" :disabled="$attributes['disabled'] ?? false" :hidden="$attributes['hidden'] ?? false" :placeholder="$attributes['placeholder'] ?? ''" />
                    @endswitch
                @endforeach
            </div>

            <div class="flex justify-end mt-4">
                <x-button type="button" style="light" onclick="resetFilter()">
                    @lang('admin::crud.reset')
                </x-button>
                <x-button type="submit">
                    @lang('admin::crud.search')
                </x-button>
            </div>
        </div>
    </form>
</x-section>

@push('scripts')
    <script>
        function resetFilter() {
            const form = document.getElementById('filterForm');
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.value = '';
            });
            form.submit();
        }
    </script>
@endpush
