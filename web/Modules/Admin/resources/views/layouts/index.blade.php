<x-admin-layout>
    <x-container>
        <x-section>
            <x-slot name="header">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">@lang('admin::crud.' . $route . '.title')</h2>

                    @canAccess($route . '.create')
                    <x-ui.button href="{{ route($route . '.create') }}" variant="primary">
                        @lang('admin::crud.create')
                    </x-ui.button>
                    @endcanAccess
                </div>
            </x-slot>

            {{-- Datatables Component --}}
            <livewire:datatables :config="$data" />
        </x-section>
    </x-container>
</x-admin-layout>
