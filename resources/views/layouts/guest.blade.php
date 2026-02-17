<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="corporate">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased @yield('body-bg')">
    <div class="min-h-screen flex flex-col sm:justify-center items-center py-2 sm:py-0">
        <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(View::hasSection('left-column'))
                <div class="flex items-center flex-col md:flex-row gap-6 w-full mt-6">
                    <!-- Columna izquierda (40%) -->
                    <div class="w-full md:w-[40%] order-1 md:order-none">
                        @yield('left-column')
                    </div>
                    <!-- Columna derecha (60%) -->
                    <div class="w-full md:w-[60%] order-2 md:order-none">
                        @yield('content')
                    </div>
                </div>
            @else
                <!-- Solo contenido principal -->
                @yield('content')
            @endif
        </div>
    </div>
</body>
</html>