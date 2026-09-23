<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Cadangkan database dompetku secara rutin atau manual, lalu unduh file .sql-nya.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Pencadangan - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Pencadangan & Restore - dompetku</title>

    <script>
        (function initTheme() {
            try {
                const savedTheme = localStorage.getItem('theme');
                const isDark = savedTheme !== 'light';
                if (isDark) document.documentElement.classList.add('dark');
                document.documentElement.style.backgroundColor = isDark ? '#0A0A0A' : '#FAFAFA';
            } catch (e) {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased">

    <x-sidebar title="Pencadangan & Restore" />

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-[#171717] rounded-2xl shadow-sm border border-neutral-200 dark:border-[#333333]">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 bg-green-50 text-green-600 border border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-neutral-700 dark:text-neutral-200">{{ session('success') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)"
         class="fixed top-20 right-6 z-50 flex items-center w-full max-w-sm p-4 bg-white dark:bg-[#171717] rounded-2xl shadow-sm border border-red-200 dark:border-red-500/30">
        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 bg-red-50 text-red-600 border border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20 rounded-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="ml-3 text-xs font-semibold text-neutral-700 dark:text-neutral-200">{{ session('error') }}</div>
        <button @click="show = false" class="ml-auto p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-white rounded-lg">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    @php
        $humanSize = function (int $bytes): string {
            if ($bytes >= 1048576) return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
            if ($bytes >= 1024) return number_format($bytes / 1024, 1, ',', '.') . ' kB';
            return $bytes . ' B';
        };
        $diskFree = function_exists('disk_free_space') && is_dir($backupDir)
            ? disk_free_space($backupDir)
            : null;
    @endphp

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('transactions.index') }}" aria-label="Kembali ke daftar transaksi"
               class="absolute left-0 flex-shrink-0 w-10 h-10 rounded-xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="text-center">
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-50 tracking-tight">Pencadangan & Restore</h1>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Lindungi data keuanganmu dari kehilangan</p>
            </div>
        </div>

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-5 sm:p-6 mb-6">
            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Cadangkan Sekarang</h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5 mb-4">
                Simpan seluruh database ke satu file <code class="text-neutral-700 dark:text-neutral-300">.sql</code>. Aman untuk diunduh kapan pun.
            </p>

            <form method="POST" action="{{ route('backups.store') }}"
                  onsubmit="return confirm('Buat backup baru sekarang? Proses biasanya hanya beberapa detik.')">
                @csrf
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
                    Buat Backup Sekarang
                </button>
            </form>

            <p class="text-[11px] text-neutral-400 dark:text-neutral-500 mt-3 leading-relaxed">
                Otomatis: backup dibuat tiap hari pukul 03:00 WIB dan 30 file terakhir dipertahankan.
                Pastikan <strong>cron</strong> di cPanel sudah diaktifkan — lihat <em>Petunjuk Cron</em> di bawah.
            </p>
        </section>

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">File Backup</h2>
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ $backups->count() }} file</span>
            </div>

            <ul class="divide-y divide-neutral-100 dark:divide-[#262626]">
                @forelse ($backups as $backup)
                <li class="flex items-center gap-3 px-5 py-3">
                    <span class="flex-shrink-0 w-9 h-9 rounded-xl bg-violet-50 text-violet-600 border border-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-neutral-900 dark:text-neutral-50 truncate">{{ $backup['filename'] }}</p>
                        <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5">{{ $backup['created_at'] }} &middot; {{ $humanSize($backup['size']) }}</p>
                    </div>
                    <a href="{{ route('backups.download', $backup['filename']) }}"
                       class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-neutral-200 dark:border-[#333333] text-xs font-semibold text-neutral-700 dark:text-neutral-200 hover:bg-neutral-50 dark:hover:bg-[#262626] transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Unduh
                    </a>
                </li>
                @empty
                <li class="px-6 py-10 text-center">
                    <p class="text-sm font-semibold text-neutral-500 dark:text-neutral-400">Belum ada backup.</p>
                    <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">Klik "Buat Backup Sekarang" untuk membuat file pertama.</p>
                </li>
                @endforelse
            </ul>

            @if ($diskFree !== null && $diskFree !== false)
            <div class="px-5 py-3 border-t border-neutral-100 dark:border-[#262626] text-[11px] text-neutral-400 dark:text-neutral-500">
                Ruang disk tersisa: {{ $humanSize((int) $diskFree) }}
            </div>
            @endif
        </section>

        <div class="grid gap-4 mt-6">
            <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-5 sm:p-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50 mb-3">Petunjuk Cron Otomatis</h2>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 leading-relaxed">
                    Backup otomatis dijalankan Laravel Scheduler lewat <strong>satu</strong> baris cron. Buka
                    <strong>cPanel &rarr; Cron Jobs</strong> lalu tambahkan:
                </p>
                <pre class="mt-3 p-3 bg-neutral-50 dark:bg-[#0A0A0A] border border-neutral-200 dark:border-[#262626] rounded-xl text-[11px] leading-relaxed overflow-x-auto text-neutral-700 dark:text-neutral-300">* * * * * /usr/local/bin/php {{ $backupDir ? str_replace('storage/app/backups', 'artisan', $backupDir) : '/home3/almahir/ibrahim_projects/laravel_finance/artisan' }} schedule:run</pre>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-3 leading-relaxed">
                    Jadwal harian 03:00 WIB akan dipicu sendiri oleh baris ini. Kode rahasia & data tidak pernah bocor —
                    backup disimpan di <code class="text-neutral-700 dark:text-neutral-300">{{ $backupDir }}</code> (di luar area web).
                </p>
            </section>

            <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-5 sm:p-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50 mb-3">Cara Restore</h2>
                <ol class="list-decimal ml-5 space-y-1.5 text-xs text-neutral-600 dark:text-neutral-400 leading-relaxed">
                    <li>Unduh file <code class="text-neutral-800 dark:text-neutral-200">{{ $backups->first()['filename'] ?? 'dompetku-...sql' }}</code> di atas, lalu simpan salinan di tempat aman.</li>
                    <li>Untuk pemulihan penuh buka <strong>phpMyAdmin</strong> di cPanel &rarr; database <code class="text-neutral-800 dark:text-neutral-200">almahir_keuangan</code> &rarr; tab <em>Import</em> &rarr; pilih file <code class="text-neutral-800 dark:text-neutral-200">.sql</code>.</li>
                    <li>File backup sudah berisi <code class="text-neutral-800 dark:text-neutral-200">DROP TABLE IF EXISTS</code>, jadi aman diimpor di atas data lama.</li>
                    <li>Praktik terbaik: coba restore ke database uji di komputer lokal sebelum benar-benar butuh.</li>
                </ol>
            </section>
        </div>

        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 text-center mt-6 px-4 leading-relaxed">
            Backup tersimpan maksimal {{ $retentionKeep }} file. Unduh dan simpan salinan di luar server
            (laptop/cloud) untuk perlindungan berlapis terhadap kegagalan disk.
        </p>
    </div>

@auth
@include('components.ai-chat')
@endauth

</body>
</html>