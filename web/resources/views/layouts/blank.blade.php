<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $pageTitle ?? config('app.name', 'Laravel') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
            integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
            crossorigin="anonymous" referrerpolicy="no-referrer" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            * {
                box-sizing: border-box;
            }

            html,
            body {
                overflow-x: hidden;
                max-width: 100%;
            }

            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            }

            .prose p {
                margin-bottom: 1.5rem;
            }

            .prose hr {
                border-top: 2px solid rgb(209 213 219);
                margin-top: 0.5rem;
                margin-bottom: 1rem;
            }

            .dark .prose hr {
                border-top-color: rgb(75 85 99);
            }

            .prose img,
            .prose video,
            .prose iframe {
                max-width: 100%;
                height: auto;
            }

            .background-container {
                background: linear-gradient(to bottom, #f0f9ff 0%, #e0f2fe 100%);
                position: relative;
            }

            .dark .background-container {
                background: linear-gradient(to bottom, #0c1220 0%, #1e293b 100%);
            }

            .background-blob {
                position: absolute;
                border-radius: 50%;
                filter: blur(100px);
                opacity: 0.12;
                pointer-events: none;
            }

            .background-blob-1 {
                width: 600px;
                height: 600px;
                background: #60a5fa;
                top: -300px;
                left: -300px;
            }

            .background-blob-2 {
                width: 500px;
                height: 500px;
                background: #93c5fd;
                top: -250px;
                right: -250px;
            }

            .dark .background-blob {
                opacity: 0.08;
            }

            footer {
                background: transparent !important;
            }
        </style>
        @stack('styles')
    </head>

    <body class="w-full">
        {{ $slot }}

        @stack('scripts')
    </body>

</html>
