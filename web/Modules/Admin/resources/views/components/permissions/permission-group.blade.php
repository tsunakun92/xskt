<div
    class="permission-group group-{{ $index }} bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div
        class="bg-gray-50 dark:bg-gray-700 px-4 py-3 rounded-t-lg border-b border-gray-200 dark:border-gray-600 flex items-center justify-between">
        <div class="flex items-center">
            <h4 class="font-medium text-gray-900 dark:text-white text-sm flex items-center">
                <i class="fas fa-layer-group mr-2 text-gray-500"></i>
                {{ $groupName }}
            </h4>
            <span id="group-counter-{{ $moduleKey ?? 'all' }}-{{ $index }}"
                class="ml-3 px-2 py-1 text-xs bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-full counter-badge">
                0/{{ count($permissions) }}
            </span>
        </div>
        <div class="flex gap-1">
            <button type="button"
                class="px-2 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 focus:ring-2 focus:outline-none focus:ring-indigo-300"
                onclick="checkAllInGroup('{{ $moduleKey ?? 'all' }}', '{{ $index }}')"
                title="{{ __('admin::permission.check_all') }}">
                <i class="fas fa-check-square text-xs"></i>
            </button>
            <button type="button"
                class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 focus:ring-2 focus:outline-none focus:ring-gray-300"
                onclick="uncheckAllInGroup('{{ $moduleKey ?? 'all' }}', '{{ $index }}')"
                title="{{ __('admin::permission.uncheck_all') }}">
                <i class="fas fa-square text-xs"></i>
            </button>
        </div>
    </div>

    <div class="p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach ($permissions as $permission)
                @include('admin::components.permissions.permission-item', [
                    'permission' => $permission,
                    'moduleKey' => $moduleKey ?? null,
                    'currentPermissions' => $currentPermissions,
                    'index' => $index,
                ])
            @endforeach
        </div>
    </div>
</div>
