<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', __('Error')) - {{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Default Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/custom.css'])

        <!-- Custom Page Styles -->
        @stack('styles')
    </head>

    <body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
        <div class="min-h-screen flex items-center justify-center">
            <!-- Error Content -->
            <main class="w-full">
                <div class="max-w-md mx-auto text-center px-6 py-12">
                    <!-- Error Icon -->
                    @if (isset($errorIcon))
                        <div
                            class="mx-auto flex items-center justify-center h-24 w-24 rounded-full {{ $iconBgClass ?? 'bg-gray-100 dark:bg-gray-800' }} mb-6">
                            {!! $errorIcon !!}
                        </div>
                    @endif

                    <!-- Error Code -->
                    @if (isset($errorCode))
                        <h1 class="text-6xl font-bold text-gray-900 dark:text-white mb-4">{{ $errorCode }}</h1>
                    @endif

                    <!-- Error Title -->
                    @if (isset($errorTitle))
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">
                            {{ $errorTitle }}
                        </h2>
                    @endif

                    <!-- Error Message -->
                    @if (isset($errorMessage))
                        <p class="text-gray-600 dark:text-gray-400 mb-8">
                            {{ $errorMessage }}
                        </p>
                    @endif

                    <!-- Action Buttons -->
                    @if (isset($errorActions) && count($errorActions) > 0)
                        <div class="flex flex-wrap justify-center items-center gap-3">
                            @foreach ($errorActions as $action)
                                @if (($action['method'] ?? 'get') === 'post')
                                    <form method="POST" action="{{ $action['href'] ?? '#' }}" class="inline-flex">
                                        @csrf
                                        <x-ui.button color="{{ $action['color'] ?? 'default' }}" type="submit"
                                            class="inline-flex">
                                            @if (isset($action['icon']))
                                                {!! $action['icon'] !!}
                                            @endif
                                            {{ $action['text'] ?? '' }}
                                        </x-ui.button>
                                    </form>
                                @else
                                    <x-ui.button color="{{ $action['color'] ?? 'default' }}"
                                        href="{{ $action['href'] ?? '#' }}" class="inline-flex">
                                        @if (isset($action['icon']))
                                            {!! $action['icon'] !!}
                                        @endif
                                        {{ $action['text'] ?? '' }}
                                    </x-ui.button>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>

        <!-- Custom Page Scripts -->
        @stack('scripts')
    </body>

</html>
