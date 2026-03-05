@if (count($modules) > 1)
    <div class="mt-4 space-y-3">
        <div
            class="bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-3 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center mb-2">
                <i class="fas fa-layer-group mr-2 text-purple-600 dark:text-purple-400"></i>
                <span
                    class="text-xs font-semibold text-purple-700 dark:text-purple-300 uppercase tracking-wide">{{ __('admin::permission.modules') }}</span>
            </div>
            <nav class="flex flex-wrap gap-2" role="tablist">
                @php
                    $isAllActive = !isset($currentModule) || $currentModule === '' || $currentModule === 'all';
                @endphp
                <button type="button"
                    class="tab-module-button px-4 py-2 rounded-full font-medium text-xs transition-all duration-200 flex items-center shadow-sm
                        {{ $isAllActive ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-md transform scale-105' : 'bg-white dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-purple-50 dark:hover:bg-purple-900/30 border border-purple-200 dark:border-purple-700' }}"
                    data-module="all">
                    <i
                        class="fas fa-th-large mr-2 {{ $isAllActive ? 'text-white' : 'text-gray-800 dark:text-gray-100' }}"></i>
                    {{ __('admin::permission.all_modules') }}
                    <span id="module-counter-all"
                        class="ml-2 px-2 py-0.5 text-xs rounded-full
                            {{ $isAllActive ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100' }}">
                        0
                    </span>
                </button>
                @foreach ($modules as $moduleKey => $moduleName)
                    @php
                        $isActive = isset($currentModule) && $currentModule === $moduleKey;
                        $moduleColorClasses = [
                            'admin' => [
                                'active' =>
                                    'bg-gradient-to-r from-blue-500 to-cyan-500 text-white shadow-md transform scale-105',
                                'inactive' =>
                                    'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-blue-50 dark:hover:bg-blue-900/30 border border-blue-200 dark:border-blue-700',
                                'icon' => 'text-gray-800 dark:text-gray-100',
                                'badge' => 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100',
                            ],
                            'hr' => [
                                'active' =>
                                    'bg-gradient-to-r from-emerald-500 to-teal-500 text-white shadow-md transform scale-105',
                                'inactive' =>
                                    'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700',
                                'icon' => 'text-gray-800 dark:text-gray-100',
                                'badge' => 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100',
                            ],
                            'crm' => [
                                'active' =>
                                    'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-md transform scale-105',
                                'inactive' =>
                                    'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-orange-50 dark:hover:bg-orange-900/30 border border-orange-200 dark:border-orange-700',
                                'icon' => 'text-gray-800 dark:text-gray-100',
                                'badge' => 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100',
                            ],
                            'api' => [
                                'active' =>
                                    'bg-gradient-to-r from-violet-500 to-purple-500 text-white shadow-md transform scale-105',
                                'inactive' =>
                                    'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-violet-50 dark:hover:bg-violet-900/30 border border-violet-200 dark:border-violet-700',
                                'icon' => 'text-gray-800 dark:text-gray-100',
                                'badge' => 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100',
                            ],
                        ];
                        $colors = $moduleColorClasses[$moduleKey] ?? [
                            'active' =>
                                'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-md transform scale-105',
                            'inactive' =>
                                'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:bg-purple-50 dark:hover:bg-purple-900/30 border border-purple-200 dark:border-purple-700',
                            'icon' => 'text-gray-800 dark:text-gray-100',
                            'badge' => 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100',
                        ];
                    @endphp
                    <button type="button"
                        class="tab-module-button px-4 py-2 rounded-full font-medium text-xs transition-all duration-200 flex items-center shadow-sm
                            {{ $isActive ? $colors['active'] : $colors['inactive'] }}"
                        data-module="{{ $moduleKey }}">
                        <i class="fas fa-cube mr-2 {{ $isActive ? 'text-white' : $colors['icon'] }}"></i>
                        {{ $moduleName }}
                        <span id="module-counter-{{ $moduleKey }}"
                            class="ml-2 px-2 py-0.5 text-xs rounded-full
                                {{ $isActive ? 'bg-white/20 text-white' : $colors['badge'] }}">
                            0
                        </span>
                    </button>
                @endforeach
            </nav>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <div class="module-actions" data-module="all">
                <button type="button"
                    class="px-2 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 focus:ring-2 focus:outline-none focus:ring-indigo-300 transition-colors"
                    onclick="checkAll('all')">
                    <i class="fas fa-check-square text-xs mr-1"></i>
                    {{ __('admin::permission.check_all') }} ({{ __('admin::permission.all_modules') }})
                </button>
                <button type="button"
                    class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 focus:ring-2 focus:outline-none focus:ring-gray-300 transition-colors"
                    onclick="uncheckAll('all')">
                    <i class="fas fa-square text-xs mr-1"></i>
                    {{ __('admin::permission.uncheck_all') }} ({{ __('admin::permission.all_modules') }})
                </button>
            </div>

            @foreach ($modules as $moduleKey => $moduleName)
                <div class="module-actions hidden" data-module="{{ $moduleKey }}">
                    <button type="button"
                        class="px-2 py-1 text-xs font-medium text-indigo-600 bg-indigo-50 border border-indigo-200 rounded hover:bg-indigo-100 focus:ring-2 focus:outline-none focus:ring-indigo-300 transition-colors"
                        onclick="checkAll('{{ $moduleKey }}')">
                        <i class="fas fa-check-square text-xs mr-1"></i>
                        {{ __('admin::permission.check_all') }} ({{ $moduleName }})
                    </button>
                    <button type="button"
                        class="px-2 py-1 text-xs font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 focus:ring-2 focus:outline-none focus:ring-gray-300 transition-colors"
                        onclick="uncheckAll('{{ $moduleKey }}')">
                        <i class="fas fa-square text-xs mr-1"></i>
                        {{ __('admin::permission.uncheck_all') }} ({{ $moduleName }})
                    </button>
                </div>
            @endforeach
        </div>
    </div>
@endif
