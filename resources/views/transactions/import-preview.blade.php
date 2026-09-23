<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Pratinjau hasil import CSV transaksi di dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Pratinjau Import - dompetku">
    <title>Pratinjau Import - dompetku</title>

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

    <x-sidebar title="Pratinjau Import" />

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-8">

        <div class="relative flex items-center justify-center mb-6">
            <a href="{{ route('transactions.import') }}" aria-label="Kembali ke halaman import"
               class="absolute left-0 flex-shrink-0 w-10 h-10 rounded-xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-[#262626] hover:text-neutral-900 flex items-center justify-center transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="text-center px-12">
                <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-neutral-50 tracking-tight">Pratinjau Import</h1>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5 truncate max-w-xs sm:max-w-sm mx-auto">{{ $filename }}</p>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-6">
            <div class="rounded-2xl border border-green-200 dark:border-green-500/30 bg-green-50 dark:bg-green-500/10 p-4 text-center">
                <p class="text-2xl font-extrabold tabular-nums text-green-700 dark:text-green-400">{{ count($valid) }}</p>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-green-700/70 dark:text-green-400/70 mt-0.5">Siap Diimport</p>
            </div>
            <div class="rounded-2xl border border-red-200 dark:border-red-500/30 bg-red-50 dark:bg-red-500/10 p-4 text-center">
                <p class="text-2xl font-extrabold tabular-nums text-red-700 dark:text-red-400">{{ count($invalid) }}</p>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-red-700/70 dark:text-red-400/70 mt-0.5">Perlu Diperbaiki</p>
            </div>
        </div>

        @if (count($valid) > 0)
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl overflow-hidden shadow-sm mb-6">
            <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Transaksi Valid</h2>
                <span class="text-xs font-semibold text-neutral-400 dark:text-neutral-500">Total: Rp {{ number_format(collect($valid)->sum('amount'), 0, ',', '.') }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs sm:text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-neutral-400 dark:text-neutral-500 border-b border-neutral-100 dark:border-[#262626] bg-neutral-50/60 dark:bg-[#262626]/40">
                            <th class="px-5 py-2.5 font-bold">Tanggal</th>
                            <th class="px-3 py-2.5 font-bold">Keterangan</th>
                            <th class="px-3 py-2.5 font-bold">Kategori</th>
                            <th class="px-3 py-2.5 font-bold">Jenis</th>
                            <th class="px-5 py-2.5 font-bold text-right">Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-[#262626]">
                        @foreach ($valid as $row)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-[#262626]/40 transition-colors">
                            <td class="px-5 py-3 text-neutral-600 dark:text-neutral-300 font-mono whitespace-nowrap">{{ $row['transaction_date'] }}</td>
                            <td class="px-3 py-3 font-medium text-neutral-900 dark:text-neutral-100">{{ $row['title'] }}</td>
                            <td class="px-3 py-3 text-neutral-600 dark:text-neutral-300">
                                <span class="inline-flex px-2 py-0.5 rounded-md bg-neutral-100 dark:bg-[#262626] border border-neutral-200 dark:border-[#333333] text-[11px] font-semibold">{{ $row['category'] }}</span>
                            </td>
                            <td class="px-3 py-3">
                                @if ($row['type'] === 'income')
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-green-700 dark:text-green-400">+ Pemasukan</span>
                                @else
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-700 dark:text-red-400">− Pengeluaran</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right font-bold tabular-nums text-neutral-900 dark:text-neutral-100 whitespace-nowrap">Rp {{ number_format((float) $row['amount'], 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @else
        <div class="rounded-2xl border border-neutral-200 dark:border-[#333333] bg-white dark:bg-[#171717] p-6 text-center mb-6">
            <p class="text-sm font-semibold text-neutral-500 dark:text-neutral-400">Tidak ada transaksi valid di file ini. Periksa baris yang bermasalah lalu unggah ulang.</p>
        </div>
        @endif

        @if (count($invalid) > 0)
        <section class="bg-white dark:bg-[#171717] border border-red-200 dark:border-red-500/30 rounded-2xl overflow-hidden shadow-sm mb-6">
            <div class="px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Transaksi Bermasalah <span class="text-neutral-400 normal-case">(tidak akan diimport)</span></h2>
            </div>
            <div class="divide-y divide-neutral-100 dark:divide-[#262626]">
                @foreach ($invalid as $item)
                <div class="flex gap-3 px-5 py-3.5">
                    <span class="flex-shrink-0 w-9 h-9 rounded-lg bg-red-50 text-red-600 border border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20 flex items-center justify-center text-xs font-bold">{{ $item['row'] }}</span>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Baris #{{ $item['row'] }}</p>
                        <ul class="mt-1 space-y-1">
                            @foreach ($item['errors'] as $error)
                            <li class="text-xs text-red-700 dark:text-red-300 flex items-start gap-1.5">
                                <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $error }}
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endforeach
            </div>
        </section>
        @endif

        <form action="{{ route('transactions.import-store') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="flex flex-col-reverse sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('transactions.import') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 hover:bg-neutral-100 dark:hover:bg-[#333333] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm font-semibold transition">
                    Pilih File Lain
                </a>
                @if (count($valid) > 0)
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan {{ count($valid) }} Transaksi
                </button>
                @endif
            </div>
        </form>
    </div>

    @include('components.export-modal')

</body>
</html>