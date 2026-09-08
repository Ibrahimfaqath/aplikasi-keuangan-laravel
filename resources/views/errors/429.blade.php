<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Terlalu Banyak Permintaan - DompetKu</title>
    <script>
        (function initTheme() {
            try {
                const savedTheme = localStorage.getItem('theme');
                if (savedTheme !== 'light') document.documentElement.classList.add('dark');
            } catch (e) {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full flex items-center justify-center px-4 py-12 bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased">
    <main class="w-full max-w-md text-center bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-8 space-y-4">
        <p class="text-5xl" aria-hidden="true">⏳</p>
        <h1 class="text-xl font-bold tracking-tight">Sabar ya, kamu terlalu cepat!</h1>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">
            Terlalu banyak permintaan. Coba lagi dalam
            <span class="font-bold text-neutral-900 dark:text-neutral-100">{{ $retryAfter ?? 60 }} detik</span>.
        </p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <a href="{{ url()->previous() === url()->current() ? route('transactions.index') : url()->previous() }}"
               class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-sm font-semibold transition">
                Kembali
            </a>
            <a href="{{ route('transactions.index') }}"
               class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-[#333333] border border-neutral-300 dark:border-[#333333] rounded-xl text-sm font-semibold transition">
                Ke Dashboard
            </a>
        </div>
    </main>
</body>
</html>
