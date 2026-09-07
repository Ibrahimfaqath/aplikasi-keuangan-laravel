<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu — aplikasi pencatatan keuangan pribadi. Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah dan aman.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url('/') }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="DompetKu">
    <meta property="og:title" content="DompetKu — Aplikasi Keuangan Pribadi">
    <meta property="og:description" content="Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah.">
    <meta property="og:url" content="{{ url('/') }}">

    <title>DompetKu — Kelola Keuangan Pribadi</title>

    <!-- Theme Init -->
    <script>
        (function() {
            try {
                var savedTheme = localStorage.getItem('theme');
                var isDark = savedTheme !== 'light';
                if (isDark) {
                    document.documentElement.classList.add('dark');
                }
                document.documentElement.style.backgroundColor = isDark ? '#0A0A0A' : '#FAFAFA';
            } catch (e) {}
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-neutral-900 dark:text-neutral-100 antialiased">
    <div class="min-h-screen flex flex-col bg-neutral-50 dark:bg-[#0A0A0A]">

        <!-- Hero Section -->
        <main class="flex-1 flex flex-col items-center justify-center px-6 pt-16 pb-20 sm:pt-24 sm:pb-28">

            <!-- Logo + Brand -->
            <div class="flex flex-col items-center gap-4 mb-10">
                <x-application-logo class="w-20 h-20 shadow-sm" />
                <div class="text-center">
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50">
                        DompetKu
                    </h1>
                    <p class="mt-2 text-base sm:text-lg text-neutral-500 dark:text-neutral-400 max-w-md">
                        Kelola keuangan pribadi dengan mudah, aman, dan cerdas.
                    </p>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="flex items-center gap-3 mb-16">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center px-6 py-3 rounded-xl bg-neutral-900 hover:bg-neutral-800 dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white font-semibold text-sm shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:ring-offset-2 dark:focus:ring-offset-[#0A0A0A]">
                    Masuk
                </a>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center px-6 py-3 rounded-xl bg-white dark:bg-[#171717] hover:bg-neutral-50 dark:hover:bg-[#262626] text-neutral-900 dark:text-neutral-100 font-semibold text-sm border border-neutral-300 dark:border-[#333333] shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-neutral-900 focus:ring-offset-2 dark:focus:ring-offset-[#0A0A0A]">
                    Daftar Gratis
                </a>
            </div>

            <!-- Feature Highlights -->
            <div class="w-full max-w-lg space-y-4">

                <!-- Feature 1: Pencatatan -->
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659 1.171-1.671.288.459A8.25 8.25 0 0 1 18 10.5a8.25 8.25 0 0 1-5.885 7.898.75.75 0 0 0-.393.112l-.234.176a.75.75 0 0 1-.99-.19l-.54-.705a.75.75 0 0 0-.27-.346M12 6a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">Pencatatan Instan</h3>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Catat pemasukan & pengeluaran dalam hitungan detik. Lihat ringkasan harian, mingguan, dan bulanan.</p>
                    </div>
                </div>

                <!-- Feature 2: AI -->
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-neutral-100 dark:bg-[#262626] text-neutral-900 dark:text-neutral-100 border border-neutral-200 dark:border-[#333333] flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">Asisten AI</h3>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Scan struk belanja otomatis dengan OCR & tanya data keuangannya kapan saja lewat chat.</p>
                    </div>
                </div>

                <!-- Feature 3: Anggaran & Laporan -->
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-neutral-100 dark:bg-[#262626] text-neutral-900 dark:text-neutral-100 border border-neutral-200 dark:border-[#333333] flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900 dark:text-neutral-50">Anggaran & Laporan</h3>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Atur batas anggaran bulanan dan export laporan ke PDF atau Excel kapan saja.</p>
                    </div>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center pb-8">
            <p class="text-xs text-neutral-400 dark:text-neutral-500">© {{ date('Y') }} DompetKu — Kelola keuanganmu dengan mudah dan aman.</p>
        </footer>
    </div>
</body>
</html>
