<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="DompetKu — aplikasi pencatatan keuangan pribadi. Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah dan aman.">
    <meta name="theme-color" content="#0A1128">
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
                if (savedTheme !== 'light') {
                    document.documentElement.classList.add('dark');
                }
            } catch (e) {}
        })();
    </script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-slate-900 dark:text-white antialiased">
    <div class="min-h-screen flex flex-col bg-gradient-to-b from-blue-50 via-white to-blue-50/70 dark:from-navy-950 dark:via-navy-950 dark:to-navy-900">

        <!-- Hero Section -->
        <main class="flex-1 flex flex-col items-center justify-center px-6 pt-16 pb-20 sm:pt-24 sm:pb-28">

            <!-- Logo + Brand -->
            <div class="flex flex-col items-center gap-4 mb-10">
                <x-application-logo class="w-20 h-20 drop-shadow-xl" />
                <div class="text-center">
                    <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                        DompetKu
                    </h1>
                    <p class="mt-2 text-base sm:text-lg text-slate-500 dark:text-white/70 max-w-md">
                        Kelola keuangan pribadi dengan mudah, aman, dan cerdas.
                    </p>
                </div>
            </div>

            <!-- CTA Buttons -->
            <div class="flex items-center gap-3 mb-16">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 dark:bg-navy-400 dark:hover:bg-navy-300 text-white dark:text-navy-950 font-semibold text-sm shadow-lg shadow-blue-600/20 dark:shadow-navy-400/20 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-navy-950">
                    Masuk
                </a>
                <a href="{{ route('register') }}"
                   class="inline-flex items-center px-6 py-3 rounded-xl bg-white dark:bg-navy-800 hover:bg-slate-50 dark:hover:bg-navy-700 text-slate-700 dark:text-white/90 font-semibold text-sm border border-slate-200 dark:border-navy-700 shadow-sm transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-navy-950">
                    Daftar Gratis
                </a>
            </div>

            <!-- Feature Highlights -->
            <div class="w-full max-w-lg space-y-4">

                <!-- Feature 1: Pencatatan -->
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/70 dark:bg-navy-900/70 border border-slate-200/80 dark:border-navy-800/80 backdrop-blur-sm">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659 1.171-1.671.288.459A8.25 8.25 0 0 1 18 10.5a8.25 8.25 0 0 1-5.885 7.898.75.75 0 0 0-.393.112l-.234.176a.75.75 0 0 1-.99-.19l-.54-.705a.75.75 0 0 0-.27-.346M12 6a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Pencatatan Instan</h3>
                        <p class="text-xs text-slate-500 dark:text-white/60 mt-0.5">Catat pemasukan & pengeluaran dalam hitungan detik. Lihat ringkasan harian, mingguan, dan bulanan.</p>
                    </div>
                </div>

                <!-- Feature 2: AI -->
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/70 dark:bg-navy-900/70 border border-slate-200/80 dark:border-navy-800/80 backdrop-blur-sm">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Asisten AI</h3>
                        <p class="text-xs text-slate-500 dark:text-white/60 mt-0.5">Scan struk belanja otomatis dengan OCR & tanya data keuangannya kapan saja lewat chat.</p>
                    </div>
                </div>

                <!-- Feature 3: Anggaran & Laporan -->
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/70 dark:bg-navy-900/70 border border-slate-200/80 dark:border-navy-800/80 backdrop-blur-sm">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Anggaran & Laporan</h3>
                        <p class="text-xs text-slate-500 dark:text-white/60 mt-0.5">Atur batas anggaran bulanan dan export laporan ke PDF atau Excel kapan saja.</p>
                    </div>
                </div>

            </div>
        </main>

        <!-- Footer -->
        <footer class="text-center pb-8">
            <p class="text-xs text-slate-400 dark:text-white/40">© {{ date('Y') }} DompetKu — Kelola keuanganmu dengan mudah dan aman.</p>
        </footer>
    </div>
</body>
</html>
