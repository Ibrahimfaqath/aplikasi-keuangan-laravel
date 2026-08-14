<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="DompetKu — aplikasi pencatatan keuangan pribadi. Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah dan aman.">
        <meta name="theme-color" content="#10b981">
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Open Graph -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="DompetKu">
        <meta property="og:title" content="{{ $title ?? 'DompetKu — Aplikasi Keuangan Pribadi' }}">
        <meta property="og:description" content="Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah.">
        <meta property="og:url" content="{{ url()->current() }}">

        <title>{{ $title ?? 'DompetKu — Aplikasi Keuangan Pribadi' }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
