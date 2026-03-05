@foreach (config('menu') as $item)
    @if (isset($item['route']) && !empty($item['route']) && empty($item['children']) && !has_route($item['route']))
        @continue
    @endif

    @php
        $itemModule = $item['module'] ?? 'admin';
        $hasModulePermission = isset($item['route']) ? can_access($item['route']) : true;
    @endphp

    @if (isset($item['children']))
        @php
            $isActiveParent = false;
            $hasAccessibleChild = false;
            foreach ($item['children'] as $child) {
                // Check if route exists
                if (isset($child['route']) && !empty($child['route']) && !has_route($child['route'])) {
                    continue;
                }
                // Check if user has permission for this child
                if (can_access($child['route'])) {
                    $hasAccessibleChild = true;
                }
                // Check if current route matches this child for active state
                if (request()->routeIs($child['route'])) {
                    $isActiveParent = true;
                }
            }
        @endphp

        {{-- Only show parent menu if user has access to module and at least one child --}}
        @if ($hasModulePermission && $hasAccessibleChild)
            <div x-data="{ open: false }" class="relative" @click.away="open = false">
                <button @click="open = !open"
                    class="inline-flex items-center p-2 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out {{ $isActiveParent
                        ? 'border-blue-700 text-blue-700 focus:outline-none focus:border-blue-700'
                        : 'border-transparent text-black hover:text-blue-500 hover:border-blue-500 focus:outline-none focus:text-blue-500 focus:border-blue-500' }}">
                    @if (!empty($item['icon']))
                        <i class="{{ $item['icon'] }} mr-2"></i>
                    @endif
                    {{ __($itemModule . '::menu.' . $item['label']) }}
                    <i class="fas fa-chevron-down ml-2 text-xs transition-transform duration-200"
                        :class="{ 'transform rotate-180': open }"></i>
                </button>

                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute left-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
                    style="display: none;">
                    <div class="py-1" role="menu" aria-orientation="vertical">
                        @foreach ($item['children'] as $child)
                            @if (isset($child['route']) && !empty($child['route']) && !has_route($child['route']))
                                @continue
                            @endif
                            @php
                                $childModule = $child['module'] ?? $itemModule;
                            @endphp
                            @canAccess($child['route'])
                            <a href="{{ route($child['route']) }}" @click="open = false"
                                class="block px-4 py-2 text-sm text-black hover:text-blue-500 hover:bg-gray-50 {{ request()->routeIs($child['route']) ? 'bg-gray-50 text-blue-700' : '' }}"
                                role="menuitem" @if (request()->routeIs($child['route'])) aria-current="page" @endif>
                                @if (!empty($child['icon']))
                                    <i class="{{ $child['icon'] }} mr-2"></i>
                                @endif
                                {{ __($childModule . '::menu.' . $child['label']) }}
                            </a>
                            @endcanAccess
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- For single menu items without children --}}
        @canAccess($item['route'])
        <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['route'])" class="text-black hover:text-blue-500">
            @if (!empty($item['icon']))
                <i class="{{ $item['icon'] }} mr-2"></i>
            @endif
            {{ __($itemModule . '::menu.' . $item['label']) }}
        </x-nav-link>
        @endcanAccess
    @endif
@endforeach
