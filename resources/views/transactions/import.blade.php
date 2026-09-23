<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Import banyak transaksi sekaligus dari file CSV di dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Import Transaksi - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Import Transaksi - dompetku</title>

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

    <x-sidebar title="Import Transaksi" />

    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
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

    <div class="max-w-2xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('transactions.index') }}" aria-label="Kembali ke daftar transaksi"
               class="absolute left-0 flex-shrink-0 w-10 h-10 rounded-xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="text-center">
                <h1 class="app-page-heading text-xl sm:text-2xl">Import transaksi</h1>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Catat banyak transaksi sekaligus dari file CSV</p>
            </div>
        </div>

        @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
        <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-3.5 mb-5">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                Mode demo — import terkunci. Data hanya bisa dilihat, bukan diubah.
                <a href="{{ route('register') }}" class="underline underline-offset-2 hover:text-amber-900 dark:hover:text-amber-200">Daftar gratis</a> untuk mencoba mencatat.
            </p>
        </div>
        @endif

        <form action="{{ route('transactions.import-preview') }}" method="POST" enctype="multipart/form-data" class="space-y-6" x-data="importUpload()">
            @csrf

            <input type="file" name="file" id="fileInput" x-ref="fileInput" accept=".csv,.txt,text/csv" class="hidden" @change="fileSelected($event)">

            <button type="button"
                    @click="$refs.fileInput.click()"
                    class="w-full relative border-2 border-dashed border-neutral-300 dark:border-[#333333] hover:border-neutral-900 dark:hover:border-neutral-100 rounded-2xl p-8 sm:p-10 text-center bg-neutral-50 dark:bg-[#262626]/40 hover:bg-neutral-100 dark:hover:bg-[#262626] transition cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                <div x-show="!fileName" class="space-y-3">
                    <div class="w-14 h-14 mx-auto bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-neutral-900 dark:text-neutral-50">
                            <span class="text-neutral-900 dark:text-white font-bold">Klik</span> atau seret file CSV ke sini
                        </p>
                        <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1">CSV / TXT — maks 5MB dan 500 baris data</p>
                    </div>
                </div>
                <div x-show="fileName" x-cloak class="space-y-2">
                    <div class="w-12 h-12 mx-auto bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <p class="text-sm font-bold text-neutral-900 dark:text-neutral-50 truncate px-6" x-text="fileName"></p>
                    <p class="text-xs text-neutral-400 dark:text-neutral-500">Klik untuk mengganti file</p>
                </div>
            </button>
            @error('file')
                <p class="text-xs text-red-600 dark:text-red-400 font-medium mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ $message }}
                </p>
            @enderror

            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-2">
                <a href="{{ route('transactions.import-template') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-[#333333] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm font-semibold transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v13.5m0 0l-4.5-4.5M12 16.5l4.5-4.5M3 21h18"/></svg>
                    Unduh Contoh Template
                </a>
                @if (\App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user()))
                <span class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 border border-neutral-200 dark:border-[#333333] rounded-xl text-xs sm:text-sm font-semibold cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
                    Pratinjau Terkunci (Mode Demo)
                </span>
                @else
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    Pratinjau & Validasi
                </button>
                @endif
            </div>
        </form>

        <div class="mt-8 bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl p-5 sm:p-6 shadow-sm">
            <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-neutral-400 dark:text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                Format Kolom
            </h2>
            <p class="text-xs text-neutral-500 dark:text-neutral-400 mb-4">Baris pertama harus nama kolom (koma atau titik koma). Kolom bisa berurutan bebas.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="text-left border-b border-neutral-200 dark:border-[#333333]">
                            <th class="py-2 pr-3 font-bold text-neutral-900 dark:text-neutral-50">Kolom</th>
                            <th class="py-2 pr-3 font-bold text-neutral-900 dark:text-neutral-50">Contoh</th>
                            <th class="py-2 font-bold text-neutral-900 dark:text-neutral-50">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-[#262626]">
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-neutral-900 dark:text-neutral-100">Tanggal Transaksi</td>
                            <td class="py-2 pr-3 text-neutral-600 dark:text-neutral-300 font-mono">2026-09-23</td>
                            <td class="py-2 text-neutral-500 dark:text-neutral-400">Wajib. Format bebas (Y-m-d, d/m/Y, dst).</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-neutral-900 dark:text-neutral-100">Keterangan / Judul</td>
                            <td class="py-2 pr-3 text-neutral-600 dark:text-neutral-300 font-mono">Belanja Mingguan</td>
                            <td class="py-2 text-neutral-500 dark:text-neutral-400">Wajib.</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-neutral-900 dark:text-neutral-100">Kategori</td>
                            <td class="py-2 pr-3 text-neutral-600 dark:text-neutral-300 font-mono">Belanja</td>
                            <td class="py-2 text-neutral-500 dark:text-neutral-400">Wajib. Harus kategori yang tersedia (peka huruf besar/kecil).</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-neutral-900 dark:text-neutral-100">Jenis Transaksi</td>
                            <td class="py-2 pr-3 text-neutral-600 dark:text-neutral-300 font-mono">Pengeluaran</td>
                            <td class="py-2 text-neutral-500 dark:text-neutral-400">"Pemasukan" atau "Pengeluaran". Boleh kosong bila nominal diawali tanda − / +.</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-neutral-900 dark:text-neutral-100">Nominal</td>
                            <td class="py-2 pr-3 text-neutral-600 dark:text-neutral-300 font-mono">25000</td>
                            <td class="py-2 text-neutral-500 dark:text-neutral-400">Wajib. Dukung "25.000", "25000,50", "Rp 5.000".</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 rounded-xl bg-neutral-50 dark:bg-[#262626]/60 border border-neutral-200 dark:border-[#333333] px-3.5 py-3">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Tips</p>
                <p class="text-xs text-neutral-600 dark:text-neutral-300 mt-1 leading-relaxed">
                    File CSV hasil <strong>Export Laporan (Excel/PDF)</strong> dan template di atas otomatis dikenali.
                    Paling mudah: unduh template, isi barisnya, lalu unggah kembali.
                </p>
            </div>
        </div>
    </div>

    <script>
        function importUpload() {
            return {
                fileName: '',
                chosenBy: null,
                fileSelected(e) {
                    const f = e.target.files && e.target.files[0];
                    this.fileName = f ? f.name : '';
                }
            };
        }
    </script>

    @include('components.export-modal')

</body>
</html>
