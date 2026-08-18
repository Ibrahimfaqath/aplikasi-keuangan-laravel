<!DOCTYPE html>
<html lang="id" class="h-full bg-white dark:bg-navy-950">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $metaDescription ?? 'DompetKu — aplikasi keuangan pribadi: catat pemasukan, pengeluaran, dan atur anggaran bulanan.' }}">
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

    <!-- 1. KUNCI WARNA CANVAS BROWSER (MEMATIKAN LIGHT FLASH SAMA SEKALI) -->
    <script>
        (function() {
            var savedTheme = localStorage.getItem('theme');
            // Default tema gelap navy (#0A1128) — mode terang hanya jika user memilihnya
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

    <style>
        body { overflow-x: hidden; }
        [x-cloak] { display: none !important; }

        /* Custom select dropdown arrow — professional & konsisten */
        .select-field {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem !important;
        }
        .dark .select-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        }

        /* Date input — ikon kalender menggantikan panah native */
        .date-field {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.15em 1.15em;
            padding-right: 2.5rem !important;
        }
        .dark .date-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'/%3e%3c/svg%3e");
        }

        /* Upload button active state */
        .btn-upload.active {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }
        .dark .btn-upload.active {
            background-color: #3b63b8 !important;
            border-color: #3b63b8 !important;
            color: #ffffff !important;
        }

        /* Chip kategori aktif */
        .cat-chip.active {
            background-color: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .dark .cat-chip.active {
            background-color: #3b63b8 !important;
            border-color: #3b63b8 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(59, 99, 184, 0.35);
        }
        /* Ikon di dalam chip aktif ikut berwarna putih */
        .cat-chip.active > span:first-child {
            background-color: rgba(255, 255, 255, 0.16) !important;
            color: #ffffff !important;
        }
        /* Baris kategori: sembunyikan scrollbar horizontal */
        .cat-row::-webkit-scrollbar { display: none; }
        .cat-row { scrollbar-width: none; }

        /* Skeleton shimmer */
        @keyframes skeleton-shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .animate-shimmer {
            background: linear-gradient(90deg, rgba(229, 231, 235, 0.8) 25%, rgba(243, 244, 246, 0.9) 37%, rgba(229, 231, 235, 0.8) 63%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.4s infinite ease-in-out;
        }
        .dark .animate-shimmer {
            background: linear-gradient(90deg, rgba(16, 29, 60, 0.8) 25%, rgba(21, 38, 73, 0.9) 37%, rgba(16, 29, 60, 0.8) 63%);
            background-size: 200% 100%;
            animation: skeleton-shimmer 1.4s infinite ease-in-out;
        }

        img[loading="lazy"] {
            background: #f1f5f9;
        }
        .dark img[loading="lazy"] {
            background: #101d3c;
        }

        /* Print: hanya konten laporan yang tampil */
        @media print {
            body { background-color: #ffffff !important; color: #000000 !important; }
            header, form, button, .no-print, nav, #exportModal, .fixed { display: none !important; }
            .print-only { display: block !important; }
            .shadow-sm, .shadow-md, .shadow-xl, .shadow-lg { box-shadow: none !important; border: 1px solid #ccc !important; }
        }

        /* Halaman AI: animasi bubble + titik mengetik + scrollbar halus */
        @keyframes bubble-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .bubble { animation: bubble-in .25s ease both; }

        @keyframes typing-bounce {
            0%, 80%, 100% { transform: translateY(0); opacity: .5; }
            40% { transform: translateY(-4px); opacity: 1; }
        }
        .typing-dot { animation: typing-bounce 1.2s infinite ease-in-out; }
        .typing-dot:nth-child(2) { animation-delay: .15s; }
        .typing-dot:nth-child(3) { animation-delay: .3s; }

        #chatScroll::-webkit-scrollbar { width: 6px; }
        #chatScroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; }
        .dark #chatScroll::-webkit-scrollbar-thumb { background: #404040; }

        /* Sembunyikan bottom nav saat kolom chat difokus (keyboard terbuka di mobile) */
        body.chat-input-focus #bottomNav { transform: translateY(110%); }
    </style>
</head>

<body class="min-h-full bg-white dark:bg-navy-950 text-slate-900 dark:text-white font-sans antialiased flex flex-col">

    <!-- NAVBAR (komponen terpadu) -->
    <x-navbar />

    <!-- MAIN CONTENT -->
    <main class="flex-1 w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 {{ $containerClass ?? 'max-w-7xl' }}">
        @yield('content')
    </main>

    <!-- FOOTER -->
    <x-footer />

    @stack('scripts')
</body>
</html>
