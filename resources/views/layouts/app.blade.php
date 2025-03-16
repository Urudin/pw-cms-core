<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Innováció Menedzsment') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-200 overflow-x-hidden"> <!-- Megakadályozza a horizontális scrollt -->

<!-- Full-Width Header with Centered Content -->
<header class="w-full">
    @include('components.header')
</header>

<!-- Main Content Wrapper with Full Width on Small Screens -->
<main class="bg-white w-full py-10 overflow-hidden">
    <div class="w-full max-w-screen-xl mx-auto px-4 sm:px-6">
        @yield('content')
    </div>
</main>

<!-- Footer -->
<footer class="w-full py-6 bg-gray-900 text-white overflow-hidden">
    <div class="w-full max-w-screen-xl mx-auto px-4 sm:px-6">
        @include('components.footer')
    </div>
</footer>

</body>
</html>
