<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="DompetKu — aplikasi pencatatan keuangan pribadi. Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah dan aman.">
        <meta name="theme-color" content="#4f46e5">
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
                    var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                    if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
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
    <body class="font-sans text-slate-900 dark:text-slate-100 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 pt-8 sm:pt-0 pb-10 bg-gradient-to-b from-slate-100 via-slate-50 to-indigo-50/70 dark:from-slate-950 dark:via-slate-950 dark:to-indigo-950/40">

            <!-- Branding -->
            <a href="/" class="flex flex-col items-center gap-3 group">
                <x-application-logo class="w-16 h-16 drop-shadow-lg group-hover:scale-105 transition-transform duration-200" />
                <div class="text-center">
                    <span class="block text-xl font-extrabold tracking-tight text-slate-900 dark:text-white">DompetKu</span>
                    <span class="block text-xs text-slate-500 dark:text-slate-400 mt-0.5">Aplikasi Keuangan Pribadi</span>
                </div>
            </a>

            <!-- Kartu -->
            <div class="w-full sm:max-w-md mt-6 bg-white dark:bg-slate-900 shadow-xl shadow-slate-900/5 dark:shadow-black/20 border border-slate-200/80 dark:border-slate-800 rounded-2xl overflow-hidden">
                <div class="px-6 py-6 sm:px-8 sm:py-7">
                    {{ $slot }}
                </div>
            </div>

            <p class="mt-6 text-xs text-slate-400 dark:text-slate-500">© {{ date('Y') }} DompetKu — Kelola keuanganmu dengan mudah dan aman.</p>
        </div>
    </body>
</html>
