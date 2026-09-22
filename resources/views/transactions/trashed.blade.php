<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="dompetku — kelola pemasukan, pengeluaran, dan anggaran bulanan dalam satu aplikasi pencatatan keuangan pribadi.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Sampah - dompetku">
    <title>Sampah - dompetku</title>

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

    <style>
        body { overflow-x: hidden; }
        img[loading="lazy"] { background: #F5F5F5; }
        .dark img[loading="lazy"] { background: #262626; }
        [x-cloak] { display: none !important; }

        .select-field {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23737373' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 1.25em 1.25em;
            padding-right: 2.5rem !important;
        }
        .dark .select-field {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23A3A3A3' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        }
    </style>
</head>

<body class="app-shell-content min-h-full bg-neutral-50 dark:bg-[#0A0A0A] text-neutral-900 dark:text-neutral-100 font-sans antialiased flex flex-col">

    <x-sidebar title="Sampah" />

    @if(session('success'))
    <div id="toast-success" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
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
    <div id="toast-error" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)"
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

    <div class="flex-1 w-full min-w-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-8 space-y-6 sm:space-y-6 overflow-x-hidden">

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl p-4 sm:p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="p-2.5 bg-red-50 text-red-600 border border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20 rounded-xl flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-base sm:text-lg font-bold tracking-tight text-neutral-900 dark:text-neutral-50">Sampah</h1>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">Transaksi dihapus masih bisa dipulihkan. Penghapusan permanen baru menghilangkan datanya.</p>
                    </div>
                </div>
                <a href="{{ route('transactions.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-neutral-100 hover:bg-neutral-200 dark:bg-[#262626] dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-xl text-xs font-semibold transition shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                    Kembali ke Riwayat
                </a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5 pt-5 border-t border-neutral-200 dark:border-[#333333]">
                <div class="min-w-0">
                    <p class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Di Sampah</p>
                    <p class="mt-1 whitespace-nowrap text-xl sm:text-2xl font-extrabold tracking-tight tabular-nums text-neutral-900 dark:text-neutral-50">{{ $transactions->total() }} transaksi</p>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Total Nominal</p>
                    <p class="mt-1 whitespace-nowrap text-lg font-bold tabular-nums text-red-600 dark:text-red-400 privacy-target" data-amount="Rp {{ number_format($totalExpense + $totalIncome, 0, ',', '.') }}">Rp {{ number_format($totalExpense + $totalIncome, 0, ',', '.') }}</p>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Pemasukan</p>
                    <p class="mt-1 whitespace-nowrap text-lg font-bold tabular-nums text-green-600 dark:text-green-400 privacy-target" data-amount="Rp {{ number_format($totalIncome, 0, ',', '.') }}">Rp {{ number_format($totalIncome, 0, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl p-4 sm:p-5 shadow-sm">
            <form method="GET" action="{{ route('transactions.trashed') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                <div class="lg:col-span-4 relative">
                    <label for="trashSearch" class="sr-only">Cari di sampah</label>
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-neutral-400 dark:text-neutral-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" id="trashSearch" name="search" value="{{ request('search') }}" placeholder="Cari transaksi di sampah..." autocomplete="off"
                           class="w-full pl-11 pr-4 py-2.5 bg-white dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 focus:outline-none focus:border-neutral-900 dark:focus:border-neutral-100 focus:ring-1 focus:ring-neutral-900 dark:focus:ring-neutral-100 transition">
                </div>

                <div class="lg:col-span-3">
                    <x-custom-select name="type" id="trashType" label="Filter berdasarkan tipe transaksi"
                        :options="['' => 'Semua Tipe', 'income' => 'Pemasukan', 'expense' => 'Pengeluaran']"
                        :selected="request('type', '')" />
                </div>

                <div class="lg:col-span-3">
                    @php
                        $categoryFilterOptions = ['' => 'Semua Kategori'];
                        foreach (\App\Models\Category::allNames(auth()->id()) as $cat) { $categoryFilterOptions[$cat] = $cat; }
                    @endphp
                    <x-custom-select name="category" id="trashCategory" label="Filter berdasarkan kategori"
                        :options="$categoryFilterOptions" :selected="request('category', '')" :searchable="true" />
                </div>

                <div class="lg:col-span-2 flex gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-sm transition">
                        Filter
                    </button>
                    @if(request('search') || request('type') || request('category'))
                        <a href="{{ route('transactions.trashed') }}" title="Reset filter" aria-label="Reset semua filter" class="inline-flex items-center justify-center h-[38px] w-[38px] sm:h-[42px] sm:w-[42px] shrink-0 bg-white dark:bg-[#262626] text-neutral-500 dark:text-neutral-400 border border-neutral-300 dark:border-[#333333] rounded-xl text-sm font-semibold hover:bg-neutral-100 dark:hover:bg-[#333333] hover:text-neutral-900 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </div>
            </form>
        </section>

        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-4 sm:px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <h2 class="text-sm font-bold uppercase tracking-wider text-neutral-900 dark:text-neutral-50">Transaksi di Sampah</h2>
                <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">{{ $transactions->total() ?? 0 }} item</span>
            </div>

            @php
                $items = $transactions ?? [];
                $pillThemes = [
                    'green'   => 'bg-green-50 text-green-600 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20',
                    'violet'  => 'bg-violet-50 text-violet-600 border-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20',
                    'orange'  => 'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20',
                    'blue'    => 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                    'amber'   => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                    'neutral' => 'bg-neutral-100 text-neutral-600 border-neutral-200 dark:bg-[#262626] dark:text-neutral-300 dark:border-[#333333]',
                ];
                $catPillMap = [
                    'Gaji' => 'green', 'Bonus' => 'green', 'Bisnis' => 'green',
                    'Investasi' => 'violet', 'Hadiah' => 'green',
                    'Makanan & Minuman' => 'orange', 'Transportasi' => 'blue',
                    'Tagihan & Utilitas' => 'amber', 'Belanja' => 'violet',
                ];
                $pillFor = function ($cat) use ($pillThemes, $catPillMap) {
                    return $pillThemes[$catPillMap[$cat] ?? 'neutral'];
                };
            @endphp

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-neutral-50 dark:bg-[#262626]/40 border-b border-neutral-200 dark:border-[#333333] text-neutral-500 dark:text-neutral-400 text-xs font-semibold uppercase tracking-wider">
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Bukti</th>
                            <th class="py-3.5 px-4">Keterangan</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4 text-right">Nominal</th>
                            <th class="py-3.5 px-4">Dihapus</th>
                            <th class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-[#262626] text-xs sm:text-sm">
                        @forelse ($items as $item)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-[#262626]/50 transition">
                            <td class="py-4 px-4 font-medium text-neutral-500 dark:text-neutral-400 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                            </td>
                            <td class="py-4 px-4">
                                <div class="relative w-8 h-8">
                                    @if(!empty($item->image))
                                    <img src="{{ asset('storage/' . $item->image) }}"
                                         loading="lazy"
                                         class="w-8 h-8 rounded-lg object-cover border border-neutral-200 dark:border-[#333333]"
                                         alt="Bukti">
                                    @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg border border-dashed border-neutral-200 dark:border-[#333333] text-neutral-300 dark:text-neutral-600" title="Tidak ada bukti">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-4 font-semibold text-neutral-900 dark:text-neutral-50">
                                <span class="block max-w-[150px] truncate lg:max-w-[200px] xl:max-w-[260px]" title="{{ $item->title }}">{{ $item->title }}</span>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                <span class="inline-flex max-w-[140px] items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold xl:max-w-[180px] {{ $pillFor($item->category) }}" title="{{ $item->category }}">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                    <span class="truncate">{{ $item->category }}</span>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right font-extrabold whitespace-nowrap privacy-target {{ $item->type == 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                                data-amount="{{ $item->type == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount, 0, ',', '.') }}">
                                {{ $item->type == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-4 font-medium text-neutral-400 dark:text-neutral-500 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item->deleted_at)->diffForHumans() }}
                            </td>
                            <td class="py-4 px-4">
                                @php
                                    $isDemo = \App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user());
                                @endphp
                                <div class="inline-flex items-center gap-1 justify-center w-full">
                                    <form action="{{ route('transactions.restore', $item->id) }}" method="POST" class="inline">
                                        @csrf
                                        @if($isDemo)
                                        <span title="Mode demo terkunci" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 rounded-lg text-xs font-semibold cursor-not-allowed">Pulihkan</span>
                                        @else
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 hover:bg-neutral-200 dark:bg-[#262626] dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-lg text-xs font-semibold transition" title="Pulihkan transaksi">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            Pulihkan
                                        </button>
                                        @endif
                                    </form>
                                    <form action="{{ route('transactions.force-destroy', $item->id) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        @if($isDemo)
                                        <span title="Mode demo terkunci" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 rounded-lg text-xs font-semibold cursor-not-allowed">Hapus Permanen</span>
                                        @else
                                        <button type="submit" onclick="return confirm('Hapus permanen transaksi ini? Tindakan tidak bisa dibatalkan.')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/20 rounded-lg text-xs font-semibold transition" title="Hapus permanen">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            Hapus Permanen
                                        </button>
                                        @endif
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="py-16 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-neutral-100 dark:bg-[#262626] text-neutral-500 dark:text-neutral-400 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                                </div>
                                <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Sampah kosong</h3>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Tidak ada transaksi yang dihapus untuk ditampilkan.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="block md:hidden divide-y divide-neutral-100 dark:divide-[#262626]">
                @forelse ($items as $item)
                <div class="p-4">
                    <div class="flex flex-wrap gap-y-1 items-center justify-between text-xs">
                        <span class="text-neutral-400 font-medium">{{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}</span>
                        <span class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-semibold {{ $pillFor($item->category) }}">
                            {{ $item->category }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        @if(!empty($item->image))
                        <img src="{{ asset('storage/' . $item->image) }}" loading="lazy" class="w-12 h-12 rounded-xl object-cover border border-neutral-200 dark:border-[#333333]" alt="Bukti transaksi">
                        @else
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl border border-dashed border-neutral-200 dark:border-[#333333] text-neutral-300 dark:text-neutral-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z"/></svg>
                        </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-neutral-900 dark:text-neutral-50 truncate text-sm sm:text-base">{{ $item->title }}</p>
                            <p class="text-[11px] font-medium text-neutral-500 dark:text-neutral-400 mt-0.5">Dihapus {{ \Carbon\Carbon::parse($item->deleted_at)->diffForHumans() }}</p>
                        </div>
                        <p class="flex-shrink-0 font-extrabold text-sm sm:text-base whitespace-nowrap privacy-target {{ $item->type == 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                           data-amount="{{ $item->type == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount, 0, ',', '.') }}">
                            {{ $item->type == 'income' ? '+' : '−' }} Rp {{ number_format($item->amount, 0, ',', '.') }}
                        </p>
                    </div>
                    @php
                        $isDemo = \App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user());
                    @endphp
                    <div class="flex items-center justify-end gap-2 pt-3 mt-3 border-t border-neutral-100 dark:border-[#262626]">
                        <form action="{{ route('transactions.restore', $item->id) }}" method="POST" class="inline">
                            @csrf
                            @if($isDemo)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 rounded-lg text-xs font-semibold cursor-not-allowed">Pulihkan</span>
                            @else
                            <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 hover:bg-neutral-200 dark:bg-[#262626] dark:hover:bg-[#333333] text-neutral-700 dark:text-neutral-200 rounded-lg text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                Pulihkan
                            </button>
                            @endif
                        </form>
                        <form action="{{ route('transactions.force-destroy', $item->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            @if($isDemo)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 rounded-lg text-xs font-semibold cursor-not-allowed">Hapus Permanen</span>
                            @else
                            <button type="submit" onclick="return confirm('Hapus permanen transaksi ini? Tindakan tidak bisa dibatalkan.')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/20 rounded-lg text-xs font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                Hapus Permanen
                            </button>
                            @endif
                        </form>
                    </div>
                </div>
                @empty
                <div class="py-16 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 bg-neutral-100 dark:bg-[#262626] text-neutral-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">Sampah kosong</h3>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Tidak ada transaksi yang dihapus untuk ditampilkan.</p>
                </div>
                @endforelse
            </div>

            @if(isset($transactions) && method_exists($transactions, 'links') && $transactions->hasPages())
            <div class="px-6 py-3 border-t border-neutral-200 dark:border-[#333333] bg-neutral-50 dark:bg-[#0A0A0A]">
                {{ $transactions->links('vendor.pagination.tailwind') }}
            </div>
            @endif
        </section>

    </div>

</body>
</html>