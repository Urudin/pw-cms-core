<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="recaptcha-sitekey" content="{{ config('services.nocaptcha.sitekey') }}">
    @csrf
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-08TJH768TM"></script>
    <script>
        window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date()); gtag('config', 'G-08TJH768TM');
    </script>
    @if(isset($page))
        <title>{{ $page->meta_title ?? \App\Models\UserSetting::getValueByName('header-sub-title-row-1') . ' ' . \App\Models\UserSetting::getValueByName('header-sub-title-row-2') }}</title>
        <meta property="og:title"
              content="{{ $page->meta_title ?? \App\Models\UserSetting::getValueByName('header-sub-title-row-1') . ' ' . \App\Models\UserSetting::getValueByName('header-sub-title-row-2') }}">
        @if(!empty($page->meta_description))
            <meta name="description" content="{{ $page->meta_description }}">
            <meta property="og:description" content="{{ $page->meta_description }}">
        @endif
        @if(!empty($page->meta_keywords))
            <meta name="keywords" content="{{ $page->meta_keywords }}">
        @endif

        <!-- Open Graph Meta Tags -->
        <meta property="og:type" content="website">
        <meta property="og:image" content="{{ \App\Models\UserSetting::getHeaderUrl() }}">
        <meta property="og:url" content="{{ url()->current() }}">

    @endif
    <link rel="stylesheet" href="{{asset('simple.css')}}">
    <script src='https://cdn.tailwindcss.com'></script>
    <script defer src='https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js'></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.nocaptcha.sitekey') }}"></script>
    <style>
        .custom-label input:checked + svg {
            display: block !important;
        }
    </style>
    <style>

        html, body {
            overflow-x: hidden;
            width: 100%;
        }
    </style>
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
<main class="bg-gray-200 w-full overflow-hidden">
    <div class="w-full max-w-screen-xl mx-auto">
        @yield('content')
    </div>
</main>

<!-- Footer -->
@include('components.footer')


</body>
</html>
