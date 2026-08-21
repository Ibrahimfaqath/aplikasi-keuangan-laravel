<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="DompetKu — aplikasi pencatatan keuangan pribadi. Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah dan aman.">
        <meta name="theme-color" content="#0A1128">
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Branding / Icons -->
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">

        <!-- Open Graph -->
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="DompetKu">
        <meta property="og:title" content="{{ $title ?? 'DompetKu — Aplikasi Keuangan Pribadi' }}">
        <meta property="og:description" content="Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah.">
        <meta property="og:url" content="{{ url()->current() }}">

        <title>{{ $title ?? 'DompetKu — Aplikasi Keuangan Pribadi' }}</title>

        <!-- Theme Init (cegah flash terang/gelap) -->
        <script>
            (function() {
                try {
                    var savedTheme = localStorage.getItem('theme');
                    // Default tema gelap navy (#0A1128) — mode terang hanya jika user memilihnya
                    if (savedTheme !== 'light') {
                        document.documentElement.classList.add('dark');
                    }
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('head')
    </head>
    <body class="font-sans text-slate-900 dark:text-white antialiased">
        <div class="min-h-screen relative isolate flex flex-col sm:justify-center items-center px-4 pt-8 sm:pt-0 pb-10 overflow-hidden bg-gradient-to-b from-blue-50 via-white to-blue-50/70 dark:from-navy-950 dark:via-navy-950 dark:to-navy-900">
            <div class="page-grid absolute inset-0 -z-10 opacity-70"></div>
            <div class="absolute -top-24 left-1/2 -z-10 h-72 w-72 -translate-x-1/2 rounded-full bg-blue-400/15 blur-3xl dark:bg-navy-400/15"></div>

            <!-- Branding -->
            <a href="/" class="flex flex-col items-center gap-3 group">
                <x-application-logo class="w-16 h-16 drop-shadow-lg group-hover:scale-105 transition-transform duration-200" />
                <div class="text-center">
                    <span class="block text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">DompetKu</span>
                    <span class="block text-xs text-slate-500 dark:text-white/70 mt-0.5">Aplikasi Keuangan Pribadi</span>
                </div>
            </a>

            <!-- Kartu -->
            <div class="w-full sm:max-w-md mt-6 bg-white/95 dark:bg-navy-900/95 shadow-xl shadow-slate-900/5 dark:shadow-black/20 border border-slate-200/80 dark:border-navy-800 rounded-2xl overflow-hidden backdrop-blur">
                <div class="px-6 py-6 sm:px-8 sm:py-7">
                    {{ $slot }}
                </div>
            </div>

            <p class="mt-6 text-xs text-slate-400 dark:text-white/50">© {{ date('Y') }} DompetKu — Kelola keuanganmu dengan mudah dan aman.</p>
        </div>
    </body>
</html>
