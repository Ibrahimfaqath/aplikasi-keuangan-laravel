<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu — aplikasi keuangan pribadi: catat pemasukan, pengeluaran, dan atur anggaran bulanan.">
    <meta name="theme-color" content="#0A1128">
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="{{ $title ?? 'DompetKu — Aplikasi Keuangan Pribadi' }}">
    <meta property="og:description" content="Catat pemasukan, pengeluaran, dan atur anggaran bulanan dengan mudah.">
    <meta property="og:url" content="{{ url()->current() }}">

    <title>{{ $title ?? 'DompetKu — Aplikasi Keuangan Pribadi' }}</title>

    <!-- 1. Kunci Warna Canvas Browser (Mencegah Flash Putih Saat Reload) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('theme');
            var isDark = savedTheme !== 'light';

            if (isDark) {
                document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = '#0A1128';
            } else {
                document.documentElement.classList.remove('dark');
                document.documentElement.style.backgroundColor = '#f8fafc';
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="bg-slate-50 text-slate-900 dark:bg-navy-950 dark:text-white min-h-screen antialiased flex flex-col selection:bg-blue-500 selection:text-white">

    <!-- NAVBAR (Komponen Navigasi Utama & Bottom Bar Mobile) -->
    <x-navbar />

    <!-- MAIN CONTENT AREA -->
    <!-- pb-32 di mobile memberi ruang ~128px di bawah sehingga tombol paling bawah tidak tertutup navbar -->
    <main class="flex-grow pb-32 md:pb-12">
        {{ $slot }}
        
        <!-- Spacer cadangan khusus mobile untuk menjamin kenyamanan scroll -->
        <div class="h-6 md:hidden w-full pointer-events-none" aria-hidden="true"></div>
    </main>

    <!-- Modal Export Laporan (bisa dipanggil dari bottom bar di semua halaman layout app) -->
    @include('components.export-modal')

    @stack('scripts')
</body>
</html>