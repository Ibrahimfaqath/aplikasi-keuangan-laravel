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

        <!-- ============ NAVBAR ============ -->
        <header class="sticky top-0 z-40 w-full border-b border-neutral-200/70 dark:border-[#222222] bg-white/80 dark:bg-[#0A0A0A]/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-5 sm:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                    <x-application-logo class="h-8 w-8 rounded-lg shadow-sm" />
                    <span class="text-sm font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50">DompetKu</span>
                </a>
                <nav class="flex items-center gap-2" aria-label="Navigasi utama">
                    <a href="#fitur"
                       class="hidden rounded-lg px-3 py-2 text-sm font-semibold text-neutral-600 hover:text-neutral-900 dark:text-neutral-300 dark:hover:text-white sm:inline-flex">
                        Fitur
                    </a>
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-semibold text-neutral-700 hover:bg-neutral-100 dark:text-neutral-200 dark:hover:bg-[#171717] transition-colors">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center rounded-xl bg-neutral-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-neutral-800 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white">
                        Daftar Gratis
                    </a>
                </nav>
            </div>
        </header>

        <main class="flex-1">

            <!-- ============ HERO ============ -->
            <section class="relative overflow-hidden">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="dot-grid absolute inset-0 opacity-70 [mask-image:radial-gradient(ellipse_at_top,black,transparent_62%)]"></div>
                    <div class="absolute -top-48 left-1/2 h-[30rem] w-[42rem] -translate-x-1/2 rounded-full bg-neutral-200/60 dark:bg-[#262626]/50 blur-3xl"></div>
                </div>

                <div class="relative mx-auto w-full max-w-6xl px-5 pb-16 pt-16 sm:px-8 sm:pb-24 sm:pt-24">
                    <div class="mx-auto flex max-w-3xl flex-col items-center text-center">
                        <span class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-neutral-600 shadow-sm dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-neutral-900 dark:bg-neutral-100"></span>
                            Gratis · Aman · Asisten AI
                        </span>

                        <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 sm:text-6xl">
                            Kelola keuangan pribadimu<br class="hidden sm:block" />
                            <span class="text-ink-gradient">dengan tenang.</span>
                        </h1>

                        <p class="mt-5 max-w-xl text-base text-neutral-500 dark:text-neutral-400 sm:text-lg">
                            Catat pemasukan &amp; pengeluaran dalam hitungan detik, pantau anggaran bulanan, dan biarkan Asisten AI merangkum keuanganmu.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-neutral-900 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition-all duration-200 hover:bg-neutral-800 hover:shadow-md dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white">
                                Mulai Gratis
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 bg-white px-6 py-3.5 text-sm font-semibold text-neutral-900 shadow-sm transition-all duration-200 hover:bg-neutral-50 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-100 dark:hover:bg-[#262626]">
                                Masuk ke Akun
                            </a>
                        </div>

                        <p class="mt-6 text-xs text-neutral-400 dark:text-neutral-500">
                            Tidak perlu kartu kredit · Datamu hanya bisa diakses akunmu sendiri
                        </p>
                    </div>

                    <!-- ===== Mockup Dashboard ===== -->
                    <div class="relative mx-auto mt-16 max-w-4xl sm:mt-20">
                        <div class="rounded-3xl border border-neutral-200/80 bg-white p-4 shadow-2xl shadow-neutral-900/[0.08] dark:border-[#262626] dark:bg-[#141414] dark:shadow-black/50 sm:p-6">
                            <!-- window bar -->
                            <div class="mb-4 flex items-center gap-1.5 px-1" aria-hidden="true">
                                <span class="h-3 w-3 rounded-full bg-neutral-200 dark:bg-[#333333]"></span>
                                <span class="h-3 w-3 rounded-full bg-neutral-200 dark:bg-[#333333]"></span>
                                <span class="h-3 w-3 rounded-full bg-neutral-200 dark:bg-[#333333]"></span>
                                <span class="ml-3 hidden text-[11px] font-medium text-neutral-400 dark:text-neutral-500 sm:inline">finance.almahir.cloud</span>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <!-- Saldo card -->
                                <div class="rounded-2xl bg-neutral-900 p-5 text-white dark:bg-neutral-100 dark:text-neutral-900 sm:col-span-2">
                                    <div class="flex items-center justify-between">
                                        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Total Saldo</p>
                                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[10px] font-semibold text-neutral-300 dark:bg-neutral-900/5 dark:text-neutral-500">Bulan ini</span>
                                    </div>
                                    <p class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Rp 4.280.500</p>
                                    <div class="mt-4 flex items-center gap-2 text-xs font-semibold">
                                        <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2.5 py-1 text-green-400 dark:bg-green-600/10 dark:text-green-600">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4.5 19.5l6-6 4 4 8-8"/></svg>
                                            +12,4%
                                        </span>
                                        <span class="text-neutral-300 dark:text-neutral-500">vs bulan lalu</span>
                                    </div>
                                </div>

                                <!-- Mini stats -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col justify-between rounded-2xl border border-neutral-200 bg-white p-4 dark:border-[#262626] dark:bg-[#171717]">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Pemasukan</p>
                                        <p class="mt-2 text-lg font-extrabold text-neutral-900 dark:text-neutral-50">Rp 6,1jt</p>
                                        <p class="text-[10px] font-medium text-green-600 dark:text-green-400">+8%</p>
                                    </div>
                                    <div class="flex flex-col justify-between rounded-2xl border border-neutral-200 bg-white p-4 dark:border-[#262626] dark:bg-[#171717]">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Pengeluaran</p>
                                        <p class="mt-2 text-lg font-extrabold text-neutral-900 dark:text-neutral-50">Rp 1,9jt</p>
                                        <p class="text-[10px] font-medium text-red-600 dark:text-red-400">-3%</p>
                                    </div>
                                </div>

                                <!-- Bar chart -->
                                <div class="rounded-2xl border border-neutral-200 bg-white p-5 dark:border-[#262626] dark:bg-[#171717] sm:col-span-3">
                                    <div class="mb-4 flex items-center justify-between">
                                        <p class="text-xs font-bold tracking-wide text-neutral-900 dark:text-neutral-50">Arus Kas Mingguan</p>
                                        <div class="flex items-center gap-3 text-[11px] font-semibold text-neutral-500 dark:text-neutral-400">
                                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-green-600"></span> Pemasukan</span>
                                            <span class="flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-red-600"></span> Pengeluaran</span>
                                        </div>
                                    </div>
                                    <div class="flex h-28 items-end gap-2 sm:gap-3" aria-hidden="true">
                                        @php
                                            $bars = [
                                                ['i' => 'h-16', 'e' => 'h-9'], ['i' => 'h-24', 'e' => 'h-14'],
                                                ['i' => 'h-20', 'e' => 'h-11'], ['i' => 'h-28', 'e' => 'h-16'],
                                                ['i' => 'h-16', 'e' => 'h-20'], ['i' => 'h-24', 'e' => 'h-9'],
                                                ['i' => 'h-11', 'e' => 'h-14'],
                                            ];
                                        @endphp
                                        @foreach ($bars as $bar)
                                        <div class="flex flex-1 items-end justify-center gap-1.5">
                                            <span class="w-3 rounded-t-md bg-green-600/80 sm:w-4 {{ $bar['i'] }}"></span>
                                            <span class="w-3 rounded-t-md bg-red-600/70 sm:w-4 {{ $bar['e'] }}"></span>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ FITUR ============ -->
            <section id="fitur" class="mx-auto w-full max-w-6xl px-5 py-16 sm:px-8 sm:py-24">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Fitur</span>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 sm:text-4xl">
                        Semua yang kamu butuhkan untuk uangmu
                    </h2>
                    <p class="mt-3 text-base text-neutral-500 dark:text-neutral-400">
                        Dari mencatat transaksi sampai analisis AI — dirancang sederhana, tanpa fitur rumit.
                    </p>
                </div>

                <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature: Pencatatan -->
                    <div class="group rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-[#262626] dark:bg-[#141414]">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659 1.171-1.671.288.459A8.25 8.25 0 0 1 18 10.5a8.25 8.25 0 0 1-5.885 7.898.75.75 0 0 0-.393.112l-.234.176a.75.75 0 0 1-.99-.19l-.54-.705a.75.75 0 0 0-.27-.346M12 6a2.25 2.25 0 1 0 0 4.5 2.25 2.25 0 0 0 0-4.5Z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-neutral-900 dark:text-neutral-50">Pencatatan Instan</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Catat pemasukan &amp; pengeluaran dalam hitungan detik. Lihat ringkasan harian, mingguan, dan bulanan.</p>
                    </div>

                    <!-- Feature: AI -->
                    <div class="group rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-[#262626] dark:bg-[#141414]">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 dark:bg-[#262626] dark:text-neutral-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-neutral-900 dark:text-neutral-50">Asisten AI</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Catat lewat chat atau suara Bahasa Indonesia, lalu tanya ringkasan keuanganmu kapan saja.</p>
                    </div>

                    <!-- Feature: Budget & Laporan -->
                    <div class="group rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-[#262626] dark:bg-[#141414]">
                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-neutral-100 text-neutral-900 dark:bg-[#262626] dark:text-neutral-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-base font-bold text-neutral-900 dark:text-neutral-50">Anggaran &amp; Laporan</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Tetapkan batas anggaran bulanan dan export laporan ke PDF atau Excel kapan saja.</p>
                    </div>
                </div>
            </section>

            <!-- ============ KEAMANAN ============ -->
            <section class="border-y border-neutral-200/70 bg-white dark:border-[#222222] dark:bg-[#0F0F0F]">
                <div class="mx-auto grid w-full max-w-6xl gap-10 px-5 py-16 sm:grid-cols-3 sm:px-8 sm:py-20">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Privat</h3>
                            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Data keuanganmu hanya dapat diakses oleh akunmu sendiri.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Aman</h3>
                            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Kata sandi dienkripsi dan koneksi dilindungi untuk menjaga datamu tetap aman.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Export Mudah</h3>
                            <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Unduh laporan PDF dan Excel untuk kebutuhan pribadi maupun pekerjaan.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ CTA ============ -->
            <section class="mx-auto w-full max-w-6xl px-5 py-16 sm:px-8 sm:py-24">
                <div class="relative overflow-hidden rounded-3xl bg-neutral-900 px-6 py-14 text-center dark:bg-neutral-100 sm:px-16 sm:py-20">
                    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                        <div class="absolute -top-24 left-1/2 h-64 w-[36rem] -translate-x-1/2 rounded-full bg-neutral-500/20 blur-3xl dark:bg-neutral-900/10"></div>
                    </div>
                    <div class="relative">
                        <h2 class="text-3xl font-extrabold tracking-tight text-white dark:text-neutral-900 sm:text-4xl">
                            Mulai kelola uangmu hari ini
                        </h2>
                        <p class="mx-auto mt-3 max-w-xl text-base text-neutral-300 dark:text-neutral-500">
                            Gratis untuk selamanya. Buat akunmu sekarang dan lihat betapa mudahnya mengendalikan keuangan pribadi.
                        </p>
                        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-white px-6 py-3.5 text-sm font-semibold text-neutral-900 shadow-sm transition-transform duration-200 hover:scale-[1.02] dark:bg-neutral-900 dark:text-white">
                                Daftar Gratis
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center rounded-xl border border-white/25 px-6 py-3.5 text-sm font-semibold text-white transition-colors hover:bg-white/10 dark:border-neutral-900/25 dark:text-neutral-900 dark:hover:bg-neutral-900/10">
                                Masuk
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- ============ FOOTER ============ -->
        <footer class="border-t border-neutral-200/70 dark:border-[#222222]">
            <div class="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-4 px-5 py-8 sm:flex-row sm:px-8">
                <div class="flex items-center gap-2.5">
                    <x-application-logo class="h-7 w-7 rounded-lg" />
                    <span class="text-sm font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50">DompetKu</span>
                </div>
                <p class="text-xs text-neutral-400 dark:text-neutral-500">© {{ date('Y') }} DompetKu — Kelola keuanganmu dengan mudah dan aman.</p>
            </div>
        </footer>
    </div>
</body>
</html>