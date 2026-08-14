<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu — dashboard keuangan pribadi: pantau saldo, pemasukan, pengeluaran, dan transaksi terakhir.">
    <meta name="theme-color" content="#4f46e5">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="{{ $title ?? 'Dashboard Keuangan - DompetKu' }}">
    <meta property="og:description" content="Pantau saldo, pemasukan, pengeluaran, dan transaksi terakhir dalam satu dashboard.">
    <meta property="og:url" content="{{ url()->current() }}">

    <title>{{ $title ?? 'Dashboard Keuangan - DompetKu' }}</title>

    <!-- 1. KUNCI WARNA CANVAS BROWSER (MEMATIKAN LIGHT FLASH SAMA SEKALI) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('theme');
            var systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = savedTheme === 'dark' || (!savedTheme && systemPrefersDark);

            if (isDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = '#111827';
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.style.backgroundColor = '#f8fafc';
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100 min-h-screen antialiased flex flex-col">

    <!-- NAVBAR (komponen terpadu) -->
    <x-navbar />

    <!-- MAIN CONTENT -->
    <main class="flex-1 w-full max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- FOOTER -->
    <x-footer />

</body>
</html>
