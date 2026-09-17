<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="dompetku — aplikasi pencatatan keuangan pribadi. Catat pemasukan, pengeluaran, dan anggaran bulanan dengan mudah dan aman.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url('/') }}">

    <!-- Branding / Icons -->
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="dompetku — Aplikasi Keuangan Pribadi">
    <meta property="og:description" content="Catat pemasukan, pengeluaran, dan anggaran bulanan dengan mudah.">
    <meta property="og:url" content="{{ url('/') }}">

    <title>dompetku — Kelola Keuangan Pribadi</title>

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
<body class="font-sans text-neutral-900 antialiased dark:text-neutral-100">
    <div class="flex min-h-screen flex-col bg-neutral-50 dark:bg-[#0A0A0A]">

        <!-- ============ NAVBAR ============ -->
        <header class="sticky top-0 z-40 w-full border-b border-neutral-200/80 bg-white/85 backdrop-blur-xl dark:border-[#222222] dark:bg-[#0A0A0A]/85">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 px-4 sm:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-2.5 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100" aria-label="dompetku — kembali ke halaman utama">
                    <x-application-logo class="h-8 w-8 rounded-lg" />
                    <span class="hidden text-sm font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 sm:inline">dompetku</span>
                </a>

                <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama">
                    <a href="#fitur"
                       class="rounded-lg px-3 py-2 text-sm font-semibold text-neutral-600 transition-colors hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-neutral-100">
                        Fitur
                    </a>
                    <a href="#cara-kerja"
                       class="rounded-lg px-3 py-2 text-sm font-semibold text-neutral-600 transition-colors hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-neutral-100">
                        Cara Kerja
                    </a>
                </nav>

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <button type="button" data-theme-toggle aria-label="Ganti tema terang atau gelap" title="Ganti tema terang atau gelap"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-neutral-600 transition-colors hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-300 dark:hover:bg-[#171717] dark:focus-visible:ring-neutral-100">
                        <svg class="hidden h-[18px] w-[18px] dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="block h-[18px] w-[18px] dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>

                    <a href="{{ route('login') }}"
                       class="inline-flex h-10 items-center rounded-xl px-3 text-sm font-semibold text-neutral-700 transition-colors hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-200 dark:hover:bg-[#171717] dark:focus-visible:ring-neutral-100 sm:px-4">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}"
                       class="inline-flex h-10 items-center whitespace-nowrap rounded-xl bg-neutral-900 px-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white dark:focus-visible:ring-neutral-100 dark:focus-visible:ring-offset-[#0A0A0A] sm:px-4">
                        <span class="sm:hidden">Daftar</span><span class="hidden sm:inline">Daftar Gratis</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1">

            <!-- ============ HERO ============ -->
            <section class="relative overflow-hidden">
                <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                    <div class="dot-grid absolute inset-0 opacity-60 [mask-image:radial-gradient(ellipse_at_top,black,transparent_62%)]"></div>
                    <div class="absolute -top-44 left-1/2 h-[26rem] w-[40rem] -translate-x-1/2 rounded-full bg-neutral-200/50 blur-3xl dark:bg-[#262626]/40"></div>
                </div>

                <div class="relative mx-auto w-full max-w-6xl px-4 pb-16 pt-16 sm:px-8 sm:pb-24 sm:pt-24">
                    <div class="mx-auto flex max-w-3xl flex-col items-center text-center">
                        <span class="inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-3.5 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-neutral-600 shadow-sm dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-neutral-900 dark:bg-neutral-100" aria-hidden="true"></span>
                            Gratis · Privat · Asisten AI
                        </span>

                        <h1 class="mt-6 text-4xl font-extrabold leading-[1.08] tracking-tight text-neutral-900 dark:text-neutral-50 sm:text-6xl">
                            Paham ke mana uangmu pergi.
                        </h1>

                        <p class="mt-5 max-w-xl text-base leading-relaxed text-neutral-500 dark:text-neutral-400 sm:text-lg">
                            dompetku membantu kamu mencatat pemasukan &amp; pengeluaran, memantau anggaran bulanan, dan memahami pola keuangan lewat ringkasan AI — semuanya sederhana dan privat.
                        </p>

                        <div class="mt-8 flex w-full flex-col items-center justify-center gap-3 sm:w-auto sm:flex-row">
                            <a href="{{ route('register') }}"
                               class="inline-flex h-12 w-full items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-neutral-900 px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 focus-visible:ring-offset-[#FAFAFA] dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white dark:focus-visible:ring-neutral-100 dark:focus-visible:ring-offset-[#0A0A0A] sm:w-auto">
                                Mulai Gratis
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                            @if (\App\Services\DemoMode::isEnabled())
                            <form method="POST" action="{{ route('demo.login') }}" class="w-full sm:w-auto">
                                @csrf
                                <button type="submit"
                                        class="inline-flex h-12 w-full items-center justify-center whitespace-nowrap rounded-xl border border-neutral-300 bg-white px-6 text-sm font-semibold text-neutral-900 shadow-sm transition-colors hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 focus-visible:ring-offset-[#FAFAFA] dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-100 dark:hover:bg-[#262626] dark:focus-visible:ring-neutral-100 dark:focus-visible:ring-offset-[#0A0A0A] sm:w-auto">
                                    Coba Demo
                                </button>
                            </form>
                            @endif
                            <a href="#fitur"
                               class="inline-flex h-12 w-full items-center justify-center whitespace-nowrap rounded-xl border border-neutral-300 bg-white px-6 text-sm font-semibold text-neutral-900 shadow-sm transition-colors hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 focus-visible:ring-offset-[#FAFAFA] dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-100 dark:hover:bg-[#262626] dark:focus-visible:ring-neutral-100 dark:focus-visible:ring-offset-[#0A0A0A] sm:w-auto">
                                Lihat Fitur
                            </a>
                        </div>

                        <p class="mt-6 text-xs text-neutral-400 dark:text-neutral-500">
                            Gratis selamanya · Tanpa kartu kredit · Datamu hanya milikmu
                        </p>
                    </div>

                    <!-- ===== Preview UI Aplikasi (mengikuti dashboard asli) ===== -->
                    <div class="relative mx-auto mt-16 max-w-5xl sm:mt-20">
                        <div class="pointer-events-none absolute -inset-x-10 -top-12 -z-10 h-72 rounded-[50%] bg-neutral-200/40 blur-3xl dark:bg-[#262626]/30" aria-hidden="true"></div>

                        <div class="rounded-2xl border border-neutral-200/80 bg-white p-2.5 shadow-2xl shadow-neutral-900/[0.08] ring-1 ring-black/[0.03] dark:border-[#262626] dark:bg-[#141414] dark:shadow-black/40 sm:p-4">
                            <!-- window bar -->
                            <div class="flex items-center gap-1.5 px-2 py-1 sm:px-3" aria-hidden="true">
                                <span class="h-3 w-3 rounded-full bg-neutral-200 dark:bg-[#333333]"></span>
                                <span class="h-3 w-3 rounded-full bg-neutral-200 dark:bg-[#333333]"></span>
                                <span class="h-3 w-3 rounded-full bg-neutral-200 dark:bg-[#333333]"></span>
                                <span class="ml-3 hidden truncate text-[11px] font-medium text-neutral-400 dark:text-neutral-500 sm:inline">finance.almahir.cloud/transactions</span>
                            </div>

                            <div class="rounded-xl border border-neutral-200/80 bg-white p-4 dark:border-[#262626] dark:bg-[#171717] sm:p-5">
                                <!-- Ringkasan saldo -->
                                <div class="grid gap-3 lg:grid-cols-3">
                                    <div class="rounded-xl bg-neutral-900 p-4 text-white dark:bg-neutral-100 dark:text-neutral-900 sm:p-5 lg:col-span-2">
                                        <div class="flex items-center justify-between">
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Total Saldo</p>
                                            <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-[10px] font-semibold text-neutral-300 dark:bg-neutral-900/5 dark:text-neutral-500">Bulan ini</span>
                                        </div>
                                        <p class="mt-2 text-3xl font-extrabold tracking-tight tabular-nums sm:text-4xl">Rp 12.480.000</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="flex flex-col justify-between rounded-xl border border-neutral-200 bg-white p-4 dark:border-[#262626] dark:bg-[#171717]">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Pemasukan</p>
                                            <p class="mt-2 text-base font-extrabold tabular-nums text-green-600 dark:text-green-400 sm:text-lg">+ Rp 6,1jt</p>
                                        </div>
                                        <div class="flex flex-col justify-between rounded-xl border border-neutral-200 bg-white p-4 dark:border-[#262626] dark:bg-[#171717]">
                                            <p class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Pengeluaran</p>
                                            <p class="mt-2 text-base font-extrabold tabular-nums text-red-600 dark:text-red-400 sm:text-lg">− Rp 1,9jt</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Grafik + transaksi terakhir -->
                                <div class="mt-3 grid gap-3 lg:grid-cols-5">
                                    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-[#262626] dark:bg-[#171717] lg:col-span-3">
                                        <div class="mb-4 flex items-center justify-between gap-3">
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

                                    <div class="rounded-xl border border-neutral-200 bg-white p-4 dark:border-[#262626] dark:bg-[#171717] lg:col-span-2">
                                        <p class="text-xs font-bold tracking-wide text-neutral-900 dark:text-neutral-50">Transaksi Terakhir</p>
                                        <ul class="mt-3 space-y-0.5">
                                            <li class="flex items-center gap-2.5 rounded-lg px-1.5 py-1.5">
                                                <span class="h-2 w-2 shrink-0 rounded-full bg-green-600"></span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-[13px] font-semibold text-neutral-900 dark:text-neutral-50">Gaji PT Sejahtera</p>
                                                    <p class="truncate text-[11px] text-neutral-500 dark:text-neutral-400">Gaji · 01 Sep</p>
                                                </div>
                                                <p class="shrink-0 text-[13px] font-bold tabular-nums text-green-600 dark:text-green-400">+ Rp 5.000.000</p>
                                            </li>
                                            <li class="flex items-center gap-2.5 rounded-lg px-1.5 py-1.5">
                                                <span class="h-2 w-2 shrink-0 rounded-full bg-red-600"></span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-[13px] font-semibold text-neutral-900 dark:text-neutral-50">Makan siang warteg</p>
                                                    <p class="truncate text-[11px] text-neutral-500 dark:text-neutral-400">Makanan &amp; Minuman · 01 Sep</p>
                                                </div>
                                                <p class="shrink-0 text-[13px] font-bold tabular-nums text-red-600 dark:text-red-400">− Rp 25.000</p>
                                            </li>
                                            <li class="flex items-center gap-2.5 rounded-lg px-1.5 py-1.5">
                                                <span class="h-2 w-2 shrink-0 rounded-full bg-red-600"></span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-[13px] font-semibold text-neutral-900 dark:text-neutral-50">Token listrik</p>
                                                    <p class="truncate text-[11px] text-neutral-500 dark:text-neutral-400">Tagihan &amp; Utilitas · 28 Agu</p>
                                                </div>
                                                <p class="shrink-0 text-[13px] font-bold tabular-nums text-red-600 dark:text-red-400">− Rp 200.000</p>
                                            </li>
                                            <li class="flex items-center gap-2.5 rounded-lg px-1.5 py-1.5">
                                                <span class="h-2 w-2 shrink-0 rounded-full bg-green-600"></span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-[13px] font-semibold text-neutral-900 dark:text-neutral-50">Jualan online</p>
                                                    <p class="truncate text-[11px] text-neutral-500 dark:text-neutral-400">Bisnis · 25 Agu</p>
                                                </div>
                                                <p class="shrink-0 text-[13px] font-bold tabular-nums text-green-600 dark:text-green-400">+ Rp 750.000</p>
                                            </li>
                                            <li class="flex items-center gap-2.5 rounded-lg px-1.5 py-1.5">
                                                <span class="h-2 w-2 shrink-0 rounded-full bg-red-600"></span>
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-[13px] font-semibold text-neutral-900 dark:text-neutral-50">Nonton bioskop</p>
                                                    <p class="truncate text-[11px] text-neutral-500 dark:text-neutral-400">Hiburan · 22 Agu</p>
                                                </div>
                                                <p class="shrink-0 text-[13px] font-bold tabular-nums text-red-600 dark:text-red-400">− Rp 85.000</p>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ FITUR ============ -->
            <section id="fitur" class="mx-auto w-full max-w-6xl scroll-mt-20 px-4 py-16 sm:px-8 sm:py-24">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Fitur</p>
                    <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 sm:text-4xl">
                        Alat yang kamu butuhkan untuk mengelola uang
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-neutral-500 dark:text-neutral-400">
                        Dirancang untuk kebiasaan finansial nyata — mencatat, memahami, dan menjaga. Tanpa fitur yang ribet.
                    </p>
                </div>

                <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Pencatatan -->
                    <div class="rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm dark:border-[#262626] dark:bg-[#171717]">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:text-neutral-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-sm font-bold text-neutral-900 dark:text-neutral-50">Pencatatan Instan</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Catat pemasukan &amp; pengeluaran dalam hitungan detik, dengan kategori yang otomatis sesuai.</p>
                    </div>

                    <!-- Ringkasan -->
                    <div class="rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm dark:border-[#262626] dark:bg-[#171717]">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:text-neutral-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-sm font-bold text-neutral-900 dark:text-neutral-50">Ringkasan Keuangan</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Saldo, grafik tren minggu/bulan/tahun, dan rincian pengeluaran per kategori dalam satu dashboard.</p>
                    </div>

                    <!-- Anggaran -->
                    <div class="rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm dark:border-[#262626] dark:bg-[#171717]">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:text-neutral-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-sm font-bold text-neutral-900 dark:text-neutral-50">Anggaran Bulanan</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Tetapkan batas belanja dan pantau sisa harian agar pengeluaran tidak melewati batas.</p>
                    </div>

                    <!-- Asisten AI -->
                    <div class="rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm dark:border-[#262626] dark:bg-[#171717]">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:text-neutral-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-sm font-bold text-neutral-900 dark:text-neutral-50">Asisten AI</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Catat lewat chat Bahasa Indonesia dan tanya ringkasan keuanganmu kapan saja.</p>
                    </div>

                    <!-- Laporan -->
                    <div class="rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm dark:border-[#262626] dark:bg-[#171717]">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:text-neutral-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-sm font-bold text-neutral-900 dark:text-neutral-50">Laporan PDF &amp; Excel</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Ekspor laporan yang mengikuti filter kamu — untuk arsip pribadi maupun keperluan pekerjaan.</p>
                    </div>

                    <!-- Input Suara -->
                    <div class="rounded-2xl border border-neutral-200/80 bg-white p-6 shadow-sm dark:border-[#262626] dark:bg-[#171717]">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-900 dark:border-[#333333] dark:bg-[#262626] dark:text-neutral-100">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 18.75a6 6 0 0 0 6-6v-1.5m-6 7.5a6 6 0 0 1-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 0 1-3-3V4.5a3 3 0 1 1 6 0v8.25a3 3 0 0 1-3 3Z" />
                            </svg>
                        </div>
                        <h3 class="mt-5 text-sm font-bold text-neutral-900 dark:text-neutral-50">Input Suara</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Cukup ucapkan “beli kopi 25 ribu” — parser lokal langsung memahami tanpa perlu koneksi.</p>
                    </div>
                </div>
            </section>

            <!-- ============ CARA KERJA ============ -->
            <section id="cara-kerja" class="scroll-mt-20 border-y border-neutral-200/70 bg-white dark:border-[#222222] dark:bg-[#0F0F0F]">
                <div class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-8 sm:py-24">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Cara kerja</p>
                        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 sm:text-4xl">
                            Dari mencatat sampai memahami, hanya 3 langkah
                        </h2>
                        <p class="mt-4 text-base leading-relaxed text-neutral-500 dark:text-neutral-400">
                            Tidak perlu paham akuntansi. dompetku menyederhanakan semuanya.
                        </p>
                    </div>

                    <ol class="mt-14 grid gap-10 sm:grid-cols-3 sm:gap-8">
                        <li>
                            <span class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-200 bg-neutral-50 text-sm font-extrabold text-neutral-900 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-50" aria-hidden="true">1</span>
                            <h3 class="mt-4 text-base font-bold text-neutral-900 dark:text-neutral-50">Catat</h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Tulis manual, minta Asisten AI mencatatkan, atau ucapkan langsung. Transaksi otomatis masuk kategori yang sesuai.</p>
                        </li>
                        <li>
                            <span class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-200 bg-neutral-50 text-sm font-extrabold text-neutral-900 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-50" aria-hidden="true">2</span>
                            <h3 class="mt-4 text-base font-bold text-neutral-900 dark:text-neutral-50">Lihat &amp; Pahami</h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Saldo, tren minggu/bulan/tahun, dan rincian per kategori tersaji jelas dalam satu dashboard yang ringkas.</p>
                        </li>
                        <li>
                            <span class="flex h-10 w-10 items-center justify-center rounded-full border border-neutral-200 bg-neutral-50 text-sm font-extrabold text-neutral-900 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-50" aria-hidden="true">3</span>
                            <h3 class="mt-4 text-base font-bold text-neutral-900 dark:text-neutral-50">Jaga Anggaran</h3>
                            <p class="mt-2 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Pasang batas bulanan, pantau sisa harian, dan ekspor laporan PDF atau Excel saat dibutuhkan.</p>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- ============ TRUST ============ -->
            <section class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-8">
                <dl class="grid gap-10 sm:grid-cols-3 sm:gap-8">
                    <div class="flex flex-col items-start">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        </span>
                        <dt class="mt-4 text-sm font-bold text-neutral-900 dark:text-neutral-50">Privat</dt>
                        <dd class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Data keuanganmu hanya bisa diakses oleh akunmu sendiri — tidak dibagikan ke siapa pun.</dd>
                    </div>
                    <div class="flex flex-col items-start">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25z"/></svg>
                        </span>
                        <dt class="mt-4 text-sm font-bold text-neutral-900 dark:text-neutral-50">Aman</dt>
                        <dd class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Kata sandi dienkripsi dan koneksi dilindungi untuk menjaga datamu tetap aman.</dd>
                    </div>
                    <div class="flex flex-col items-start">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-neutral-200 bg-neutral-50 text-neutral-700 dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 6h.008v.008H6V6Z"/></svg>
                        </span>
                        <dt class="mt-4 text-sm font-bold text-neutral-900 dark:text-neutral-50">Gratis</dt>
                        <dd class="mt-1.5 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">Tanpa biaya berlangganan, tanpa kartu kredit. Seluruh fitur bisa digunakan tanpa bayar.</dd>
                    </div>
                </dl>
            </section>

            <!-- ============ CTA ============ -->
            <section class="mx-auto w-full max-w-6xl px-4 pb-16 sm:px-8 sm:pb-24">
                <div class="relative overflow-hidden rounded-3xl border border-neutral-200/80 bg-white px-6 py-14 text-center shadow-sm dark:border-[#262626] dark:bg-[#141414] sm:px-16 sm:py-20">
                    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                        <div class="dot-grid absolute inset-0 opacity-40 [mask-image:radial-gradient(ellipse_at_top,black,transparent_70%)]"></div>
                    </div>
                    <div class="relative">
                        <h2 class="text-3xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 sm:text-4xl">
                            Mulai catat keuanganmu hari ini
                        </h2>
                        <p class="mx-auto mt-4 max-w-xl text-base leading-relaxed text-neutral-500 dark:text-neutral-400">
                            Gratis selamanya — tanpa kartu kredit, tanpa biaya tersembunyi. Buat akun dan lihat ke mana uangmu pergi.
                        </p>
                        <div class="mt-8 flex w-full flex-col items-center justify-center gap-3 sm:w-auto sm:flex-row">
                            <a href="{{ route('register') }}"
                               class="inline-flex h-12 w-full items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-neutral-900 px-6 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-neutral-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white dark:focus-visible:ring-neutral-100 dark:focus-visible:ring-offset-[#141414] sm:w-auto">
                                Daftar Gratis
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </a>
                            @if (\App\Services\DemoMode::isEnabled())
                            <form method="POST" action="{{ route('demo.login') }}" class="w-full sm:w-auto">
                                @csrf
                                <button type="submit"
                                        class="inline-flex h-12 w-full items-center justify-center whitespace-nowrap rounded-xl border border-neutral-300 bg-white px-6 text-sm font-semibold text-neutral-700 shadow-sm transition-colors hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-200 dark:hover:bg-[#262626] dark:focus-visible:ring-neutral-100 dark:focus-visible:ring-offset-[#141414] sm:w-auto">
                                    Coba Demo
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('login') }}"
                               class="inline-flex h-12 w-full items-center justify-center whitespace-nowrap rounded-xl border border-neutral-300 bg-white px-6 text-sm font-semibold text-neutral-700 shadow-sm transition-colors hover:bg-neutral-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-[#333333] dark:bg-[#171717] dark:text-neutral-200 dark:hover:bg-[#262626] dark:focus-visible:ring-neutral-100 dark:focus-visible:ring-offset-[#141414] sm:w-auto">
                                Masuk
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <!-- ============ FOOTER ============ -->
        <footer class="border-t border-neutral-200/70 dark:border-[#222222]">
            <div class="mx-auto w-full max-w-6xl px-4 py-12 sm:px-8">
                <div class="grid gap-10 sm:grid-cols-3">
                    <div class="max-w-xs">
                        <a href="{{ url('/') }}" class="flex items-center gap-2.5 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100" aria-label="dompetku — kembali ke halaman utama">
                            <x-application-logo class="h-7 w-7 rounded-lg" />
                            <span class="text-sm font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50">dompetku</span>
                        </a>
                        <p class="mt-4 text-sm leading-relaxed text-neutral-500 dark:text-neutral-400">
                            Aplikasi keuangan pribadi yang mencatat, merangkum, dan membantu kamu memahami uangmu.
                        </p>
                    </div>

                    <nav aria-label="Tautan produk" class="sm:justify-self-center">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Produk</h3>
                        <ul class="mt-4 space-y-3">
                            <li>
                                <a href="#fitur" class="rounded text-sm text-neutral-600 transition-colors hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-neutral-100">Fitur</a>
                            </li>
                            <li>
                                <a href="#cara-kerja" class="rounded text-sm text-neutral-600 transition-colors hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-neutral-100">Cara Kerja</a>
                            </li>
                        </ul>
                    </nav>

                    <nav aria-label="Tautan akun" class="sm:justify-self-center">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Akun</h3>
                        <ul class="mt-4 space-y-3">
                            <li>
                                <a href="{{ route('login') }}" class="rounded text-sm text-neutral-600 transition-colors hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-neutral-100">Masuk</a>
                            </li>
                            <li>
                                <a href="{{ route('register') }}" class="rounded text-sm text-neutral-600 transition-colors hover:text-neutral-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:text-neutral-300 dark:hover:text-white dark:focus-visible:ring-neutral-100">Daftar Gratis</a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-neutral-200/70 pt-6 dark:border-[#222222] sm:flex-row">
                    <p class="text-xs text-neutral-400 dark:text-neutral-500">© {{ date('Y') }} dompetku. Hak cipta dilindungi.</p>
                    <p class="text-xs text-neutral-400 dark:text-neutral-500">Mencatat keuangan — sederhana dan privat.</p>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>