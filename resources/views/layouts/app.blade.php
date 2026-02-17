<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- Styles -->
    @vite(['resources/css/app.css'])
    @stack('styles')
</head>

<body class="antialiased @yield('body-bg')">
    <div class="min-h-screen">
        @include('layouts.navigation')
        <main  @yield('main-bg') class="pb-4">
            @yield('content')
        </main>
    </div>

    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>

</html>