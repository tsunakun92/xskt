<nav x-data="{ open: false }" class="bg-secondary border-b border-gray-700 shadow-lg no-print">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="{{ route('admin') }}" class="flex items-center space-x-2">
                    <img src="https://flowbite.com/docs/images/logo.svg" alt="Logo" class="h-8 w-auto">
                    <span class="text-black text-xl font-semibold">base</span>
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex text-black">
                <x-menu />
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center space-x-4">
                <x-change-locale class="text-black" />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-black hover:text-active-link transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        @if (can_access('changelog.index'))
                            <x-dropdown-link :href="route('changelog.index')"
                                class="text-black hover:text-active-link hover:bg-gray-50">
                                {{ __('admin::menu.changelog') }}
                            </x-dropdown-link>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                class="text-black hover:text-active-link hover:bg-gray-50"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('admin::app.logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="sm:hidden flex items-center">
                <button @click="open = !open" class="text-black hover:text-active-link focus:outline-none">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path :class="{ 'hidden': open, 'block': !open }" class="block" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'block': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden bg-white">
        <div class="p-4 flex flex-col gap-2 w-fit">
            <x-menu />
        </div>
        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-black">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-600">{{ Auth::user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-change-locale class="px-4" />
                @if (can_access('changelog.index'))
                    <x-responsive-nav-link :href="route('changelog.index')" class="text-black hover:text-active-link">
                        {{ __('admin::menu.changelog') }}
                    </x-responsive-nav-link>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="text-black hover:text-active-link">
                        {{ __('admin::app.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
