<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Default Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/custom.css'])

        <!-- Admin Styles -->
        @viteAdmin(['resources/assets/sass/admin.scss', 'resources/assets/js/admin.js'])

        <!-- Custom Page Styles -->
        @stack('styles')
        @livewireStyles
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Breadcrumb -->
            <x-container class="py-6 no-print">
                <x-breadcrumb :items="$breadcrumb ?? []" />
            </x-container>

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            <!-- Global Translations -->
            <script>
                window.translations = window.translations || {!! json_encode([
                    'hide' => __('admin::app.current_task.hide'),
                    'show' => __('admin::app.current_task.show'),
                ]) !!};
            </script>

            <!-- Custom Page Scripts -->
            @stack('scripts')
            @livewireScripts
        </div>
    </body>

</html>
