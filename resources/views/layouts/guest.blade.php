@props(['title' => null, 'subtitle' => null, 'wide' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="DompetKu — aplikasi pencatatan keuangan pribadi. Kelola pemasukan, pengeluaran, dan anggaran bulanan dengan mudah dan aman.">
        <meta name="theme-color" content="#0A0A0A">
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
                    // Default gelap (#0A0A0A) — mode terang hanya jika user memilihnya
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
        @stack('head')
    </head>
    <body class="font-sans text-neutral-900 dark:text-neutral-100 antialiased">

        <div class="relative min-h-screen flex flex-col overflow-hidden bg-neutral-50 dark:bg-[#0A0A0A]">

            <!-- Latar dekoratif monokrom -->
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="dot-grid absolute -inset-x-4 -inset-y-4 opacity-60 [mask-image:radial-gradient(ellipse_at_top,black,transparent_72%)]"></div>
                <div class="absolute -top-40 -left-32 h-[28rem] w-[28rem] rounded-full bg-neutral-200/70 dark:bg-[#262626]/50 blur-3xl"></div>
                <div class="absolute -bottom-48 -right-28 h-[26rem] w-[26rem] rounded-full bg-neutral-200/50 dark:bg-[#262626]/30 blur-3xl"></div>
            </div>

            <!-- Top brand -->
            <div class="relative z-10 w-full px-6 pt-7 sm:px-8">
                <a href="/" class="inline-flex w-max items-center gap-2.5 group" aria-label="DompetKu — kembali ke halaman utama">
                    <x-application-logo class="h-9 w-9 rounded-xl shadow-sm transition-transform duration-200 group-hover:scale-105" />
                    <span class="text-base font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50">DompetKu</span>
                </a>
            </div>

            <!-- Kartu utama -->
            <main class="relative z-10 flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-6">
                <div class="w-full {{ $wide ? 'max-w-2xl' : 'max-w-md' }}">
                    <div class="rounded-3xl border border-neutral-200/80 dark:border-[#262626] bg-white dark:bg-[#171717] p-6 shadow-xl shadow-neutral-900/[0.04] dark:shadow-black/40 sm:p-8">
                        @if ($title)
                            <div class="mb-6">
                                @isset($icon)
                                    <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-2xl border border-neutral-200 dark:border-[#333333] bg-neutral-100 dark:bg-[#262626] text-neutral-900 dark:text-neutral-100">
                                        {{ $icon }}
                                    </div>
                                @endisset
                                <h1 class="text-2xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50">{{ $title }}</h1>
                                @if ($subtitle)
                                    <p class="mt-1.5 text-sm text-neutral-500 dark:text-neutral-400">{{ $subtitle }}</p>
                                @endif
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </div>
            </main>

            <footer class="relative z-10 pb-8 text-center">
                <p class="text-xs text-neutral-400 dark:text-neutral-500">© {{ date('Y') }} DompetKu — Keuangan pribadi yang aman dan privat.</p>
            </footer>
        </div>
    </body>
</html>