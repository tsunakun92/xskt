<div class="flex justify-between pb-4">
    <div class="flex items-center">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mr-4">@lang('admin::crud.' . $route . '.title')</h1>
    </div>
    @canAccess($route . '.create')
    <div class="flex items-center">
        <x-button href="{{ route($route . '.create') }}">@lang('admin::crud.create')</x-button>
    </div>
    @endcanAccess
</div>
