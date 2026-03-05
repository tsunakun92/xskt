<x-admin-layout>
    <x-container>
        <x-section id="user-permission-management">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                <i class="fas fa-key mr-2"></i>
                {{ __('admin::permission.title') }} - {{ $model->name }}
            </h2>

            <form method="POST" action="{{ route('users.permission', $model->id) }}" class="space-y-6">
                @csrf

                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex gap-2">
                            <button type="button"
                                class="px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 focus:ring-4 focus:outline-none focus:ring-indigo-300 transition-colors"
                                onclick="checkAll()">
                                <i class="fas fa-check-square mr-1"></i>
                                {{ __('admin::permission.check_all') }}
                            </button>
                            <button type="button"
                                class="px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-300 transition-colors"
                                onclick="uncheckAll()">
                                <i class="fas fa-square mr-1"></i>
                                {{ __('admin::permission.uncheck_all') }}
                            </button>
                        </div>
                    </div>

                    <div id="permission-tab" data-current-module="all">
                        @include('admin::components.permissions.tab-module-navigation', [
                            'modules' => $modules,
                            'currentModule' => null,
                        ])

                        <div class="space-y-4 mt-4">
                            @foreach ($groupedPermissions as $moduleKey => $groups)
                                @foreach ($groups as $groupName => $permissions)
                                    @php
                                        $groupIndex = substr(md5(($moduleKey ?? 'all') . '-' . $groupName), 0, 8);
                                    @endphp
                                    <div class="permission-group-wrapper" data-module="{{ $moduleKey ?? 'all' }}">
                                        @include('admin::components.permissions.permission-group', [
                                            'index' => $groupIndex,
                                            'groupName' => $groupName,
                                            'permissions' => $permissions,
                                            'moduleKey' => $moduleKey,
                                            'currentPermissions' => $currentPermissions,
                                        ])
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-6 space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <x-button style="light" href="{{ route($route . '.index') }}">
                        {{ __('admin::crud.back') }}
                    </x-button>
                    <x-button type="submit">
                        {{ __('admin::crud.submit') }}
                    </x-button>
                </div>
            </form>
        </x-section>
    </x-container>
</x-admin-layout>

@push('scripts')
    <script src="{{ asset('modules/admin/js/permission.js') }}"></script>
@endpush
