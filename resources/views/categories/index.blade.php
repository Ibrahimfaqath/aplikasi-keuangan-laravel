<!DOCTYPE html>
<html lang="id" class="h-full bg-neutral-50 dark:bg-[#0A0A0A]">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Kelola kategori transaksi pemasukan dan pengeluaran di dompetku.">
    <meta name="theme-color" content="#0A0A0A">
    <link rel="canonical" href="{{ url()->current() }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="dompetku">
    <meta property="og:title" content="Kelola Kategori - dompetku">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Kelola Kategori - dompetku</title>

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

    <x-sidebar title="Kelola Kategori" :back="route('transactions.index')" minimal />

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

    @php
        $isDemo = \App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user());

        $groups = [
            'income' => [
                'label' => 'Pemasukan',
                'title' => 'Kategori Pemasukan',
                'desc' => 'Uang masuk: gaji, bonus, hasil jualan, dan lainnya.',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>',
                'items' => $income,
            ],
            'expense' => [
                'label' => 'Pengeluaran',
                'title' => 'Kategori Pengeluaran',
                'desc' => 'Uang keluar: belanja, tagihan, makan, dan lainnya.',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>',
                'items' => $expense,
            ],
        ];

        // Warna + ikon per kategori, konsisten dengan chip di tabel transaksi.
        $tones = [
            'green' => 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400',
            'violet' => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-400',
            'orange' => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400',
            'blue' => 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400',
            'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
            'neutral' => 'bg-neutral-100 text-neutral-500 dark:bg-[#262626] dark:text-neutral-300',
        ];
        $categoryTone = [
            'Gaji' => 'green', 'Bonus' => 'green', 'Bisnis' => 'green', 'Hadiah' => 'green',
            'Investasi' => 'violet', 'Belanja' => 'violet', 'Hiburan' => 'violet',
            'Makanan & Minuman' => 'orange', 'Transportasi' => 'blue',
            'Tagihan & Utilitas' => 'amber', 'Kesehatan' => 'amber', 'Pendidikan' => 'blue', 'Keluarga' => 'blue',
        ];
        $categoryIcon = [
            'Gaji' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>',
            'Bonus' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a1 1 0 110-4h14a1 1 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>',
            'Bisnis' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
            'Investasi' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>',
            'Hadiah' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>',
            'Makanan & Minuman' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 2v20"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>',
            'Transportasi' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.707.293V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>',
            'Tagihan & Utilitas' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
            'Belanja' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
            'Hiburan' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>',
            'Kesehatan' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
            'Pendidikan' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>',
            'Keluarga' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
        ];
        // Fallback untuk kategori tanpa ikon khusus (mis. kategori custom & "Lainnya").
        $defaultCategoryIcon = '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.569 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>';

        $itemsPayload = [
            'income' => array_column($income, 'name'),
            'expense' => array_column($expense, 'name'),
        ];
    @endphp

    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-8"
         x-data="categoryPage(@js($itemsPayload), @js($usage))"
         @keydown.escape.window="closeCategoryModal()">

        @if ($isDemo)
        <div class="flex items-start gap-2.5 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-3.5 mb-5">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <p class="text-xs font-semibold text-amber-800 dark:text-amber-300">
                Mode demo — daftar kategori hanya bisa dilihat, bukan diubah.
                <a href="{{ route('register') }}" class="underline underline-offset-2 hover:text-amber-900 dark:hover:text-amber-200">Daftar gratis</a> untuk mencoba menambah kategori.
            </p>
        </div>
        @endif

        {{-- Judul halaman + aksi utama --}}
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
            <div class="min-w-0">
                <h2 class="text-lg font-bold tracking-tight text-neutral-900 dark:text-neutral-50">Kategori Transaksi</h2>
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 leading-relaxed max-w-md">
                    Kategori bawaan sudah siap dipakai. Tambah kategori sendiri untuk menyesuaikan catatanmu.
                </p>
            </div>
            @unless ($isDemo)
            <button type="button" onclick="openCategoryModal()"
                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Tambah Kategori
            </button>
            @endunless
        </div>

        {{-- Ringkasan --}}
        <div class="grid grid-cols-3 gap-2.5 mb-5">
            <div class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-3.5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Total</p>
                <p class="text-xl font-bold tracking-tight text-neutral-900 dark:text-neutral-50 mt-1">{{ $stats['total'] }}</p>
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5 leading-snug">
                    {{ $stats['total'] - $stats['custom'] }} bawaan<br>{{ $stats['custom'] }} custom
                </p>
            </div>
            <div class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-3.5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Terpakai</p>
                <p class="text-xl font-bold tracking-tight text-neutral-900 dark:text-neutral-50 mt-1">{{ $stats['used'] }}</p>
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5">dari {{ $stats['total'] }} kategori</p>
            </div>
            <div class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-3.5">
                <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500">Belum dipakai</p>
                <p class="text-xl font-bold tracking-tight text-neutral-900 dark:text-neutral-50 mt-1">{{ $stats['unused'] }}</p>
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5">tanpa transaksi</p>
            </div>
        </div>

        {{-- Cari + saring jenis --}}
        <div class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm p-3 mb-5">
            <div class="flex flex-col sm:flex-row gap-2.5">
                <div class="relative flex-1 min-w-0">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 dark:text-neutral-500 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Cari kategori..." aria-label="Cari kategori"
                           class="w-full pl-10 pr-9 py-2.5 bg-neutral-50 dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-xs sm:text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 dark:placeholder-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900 transition">
                    <button type="button" x-show="search !== ''" @click="search = ''" x-cloak
                            aria-label="Hapus pencarian"
                            class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-neutral-400 hover:text-neutral-900 dark:hover:text-white rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex gap-1 p-1 bg-neutral-100 dark:bg-[#262626] rounded-xl border border-neutral-200 dark:border-[#333333]" role="group" aria-label="Saring jenis kategori">
                    @foreach ([['key' => 'all', 'label' => 'Semua'], ['key' => 'income', 'label' => 'Masuk'], ['key' => 'expense', 'label' => 'Keluar']] as $filter)
                    <button type="button" @click="tab = '{{ $filter['key'] }}'" :aria-pressed="tab === '{{ $filter['key'] }}'"
                            class="flex-1 sm:flex-none px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap"
                            :class="tab === '{{ $filter['key'] }}'
                                ? 'bg-white dark:bg-[#171717] text-neutral-900 dark:text-neutral-50 shadow-sm'
                                : 'text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-50'">
                        {{ $filter['label'] }}
                        <span class="opacity-60" x-text="countOf('{{ $filter['key'] }}')"></span>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Daftar kategori --}}
        @foreach ($groups as $type => $group)
        <section class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm overflow-hidden mb-5"
                 x-show="tab === 'all' || tab === '{{ $type }}'">
            <div class="flex items-center gap-3 px-4 sm:px-5 py-4 border-b border-neutral-200 dark:border-[#333333]">
                <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center {{ $type === 'income' ? 'bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400' : 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' }}">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! $group['icon'] !!}</svg>
                </span>
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-neutral-50">{{ $group['title'] }}</h3>
                    <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5 truncate">{{ $group['desc'] }}</p>
                </div>
                {{-- Tanpa pencarian cukup totalnya; saat searching tampil "cocok/total". --}}
                <span class="ml-auto shrink-0 text-[11px] font-semibold text-neutral-500 dark:text-neutral-400 bg-neutral-100 dark:bg-[#262626] rounded-full px-2.5 py-1"
                      x-text="search.trim() === '' ? countOf('{{ $type }}') : visibleCount('{{ $type }}') + '/' + countOf('{{ $type }}')"></span>
            </div>

            <ul class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 gap-2.5"
                x-show="visibleCount('{{ $type }}') > 0">
                @foreach ($group['items'] as $item)
                <li x-show="matches('{{ $type }}', @js($item['name']))"
                    class="flex items-center gap-3 rounded-xl border border-neutral-200 dark:border-[#262626] px-3.5 py-3 hover:border-neutral-300 dark:hover:border-[#3f3f3f] hover:bg-neutral-50 dark:hover:bg-[#1c1c1c] transition-colors">
                    <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center {{ $tones[$categoryTone[$item['name']] ?? 'neutral'] }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! ($categoryIcon[$item['name']] ?? $defaultCategoryIcon) !!}</svg>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-50 leading-snug line-clamp-2" title="{{ $item['name'] }}">{{ $item['name'] }}</p>
                        <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5">
                            @php($used = $usage[$item['name']] ?? 0)
                            {{ $used > 0 ? 'Dipakai di '.$used.' transaksi' : 'Belum pernah dipakai' }}
                        </p>
                    </div>

                    @if ($item['is_global'])
                    <span class="hidden sm:inline-flex shrink-0 self-start mt-0.5 text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 border border-neutral-200 dark:border-[#333333] rounded-md px-1.5 py-0.5"
                          title="Kategori bawaan — selalu tersedia, tidak bisa dihapus">Bawaan</span>
                    @else
                    <span class="hidden sm:inline-flex shrink-0 self-start mt-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 rounded-md px-1.5 py-0.5"
                          title="Kategori buatanmu — bisa dihapus">Custom</span>
                    @endif

                    @unless ($item['is_global'])
                    <form action="{{ route('categories.destroy', $item['id']) }}" method="POST" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                onclick="return confirm({{ \Illuminate\Support\Js::from('Hapus kategori "'.$item['name'].'"? Transaksi yang sudah tercatat tetap aman, hanya pilihannya yang hilang dari form.') }})"
                                class="w-8 h-8 flex items-center justify-center text-neutral-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition"
                                title="Hapus kategori {{ $item['name'] }}" aria-label="Hapus kategori {{ $item['name'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                    @endunless
                </li>
                @endforeach
            </ul>

            <div class="px-4 sm:px-5 py-10 text-center" x-show="visibleCount('{{ $type }}') === 0" x-cloak>
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                </span>
                <p class="text-xs font-semibold text-neutral-700 dark:text-neutral-200 mt-3">Tidak ada kategori yang cocok</p>
                <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-1">Coba kata kunci lain, atau tampilkan semua kategori lagi.</p>
                <button type="button" @click="resetFilters()"
                        class="mt-3 px-3.5 py-1.5 bg-white dark:bg-[#262626] border border-neutral-300 dark:border-[#333333] rounded-xl text-[11px] font-semibold text-neutral-700 dark:text-neutral-200 hover:border-neutral-900 dark:hover:border-neutral-100 transition">
                    Tampilkan semua
                </button>
            </div>
        </section>
        @endforeach

        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 text-center mt-6 px-4 leading-relaxed">
            Kategori bawaan selalu tersedia dan tidak bisa dihapus. Menghapus kategori custom
            tidak menghapus riwayat — kategori lama tetap tersimpan pada tiap transaksi.
        </p>

        {{-- Modal tambah kategori (di dalam scope Alpine halaman) --}}
        <div id="categoryModal" class="fixed inset-0 z-[60] hidden overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <div class="fixed inset-0 bg-neutral-900/60 dark:bg-black/70 backdrop-blur-sm" onclick="closeCategoryModal()"></div>

            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-left shadow-sm w-full sm:max-w-md">

                    <div class="flex items-center justify-between p-6 border-b border-neutral-200 dark:border-[#333333]">
                        <div class="flex items-center gap-3">
                            <span class="p-2.5 bg-neutral-100 dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 rounded-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M17 7h.01M12 3v18M5 12h14"/></svg>
                            </span>
                            <div>
                                <h3 id="category-modal-title" class="text-base font-bold text-neutral-900 dark:text-neutral-50">Tambah Kategori</h3>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400">Langsung tersedia di form transaksi &amp; asisten AI</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeCategoryModal()" aria-label="Tutup"
                                class="text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form action="{{ route('categories.store') }}" method="POST" class="p-6 space-y-4">
                        @csrf

                        <div>
                            <label for="category-name" class="block text-xs font-semibold text-neutral-600 dark:text-neutral-300 mb-2">Nama Kategori</label>
                            <input type="text" name="name" id="category-name" x-model="name" value="{{ old('name') }}"
                                   placeholder="Contoh: Jualan Online" autocomplete="off"
                                   maxlength="50" required
                                   class="w-full px-4 py-3 bg-neutral-50 dark:bg-[#262626] border rounded-xl text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 dark:placeholder-neutral-500 focus:outline-none focus:ring-2 transition"
                                   :class="isDuplicate() || $errors->has('name')
                                       ? 'border-red-400 dark:border-red-500/60'
                                       : 'border-neutral-300 dark:border-[#333333] focus:ring-neutral-900 dark:focus:ring-neutral-100 focus:border-neutral-900'">
                            <div class="mt-1.5 min-h-[16px]">
                                @error('name')
                                    <p class="text-[11px] font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                                @else
                                    <p x-show="isDuplicate()" x-cloak class="text-[11px] font-semibold text-red-600 dark:text-red-400">Kategori ini sudah dipakai pada jenis transaksi tersebut.</p>
                                    <p x-show="! isDuplicate()" class="text-[11px] text-neutral-400 dark:text-neutral-500">Maksimal 50 karakter · tersisa <span x-text="50 - name.length"></span></p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-neutral-600 dark:text-neutral-300 mb-2">Jenis Transaksi</span>
                            <div class="grid grid-cols-2 gap-1 p-1 bg-neutral-100 dark:bg-[#262626] rounded-xl border border-neutral-200 dark:border-[#333333]" role="radiogroup" aria-label="Jenis Transaksi">
                                @foreach (['income' => 'Pemasukan', 'expense' => 'Pengeluaran'] as $value => $label)
                                <label class="relative flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg cursor-pointer select-none transition-colors has-[:checked]:bg-white dark:has-[:checked]:bg-[#171717] has-[:checked]:text-neutral-900 dark:has-[:checked]:text-neutral-50 has-[:checked]:shadow-sm text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-50">
                                    <input type="radio" name="type" value="{{ $value }}" x-model="type" class="sr-only" {{ old('type', 'income') === $value ? 'checked' : '' }} required>
                                    <span class="text-xs sm:text-sm font-bold">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            @error('type')
                                <p class="text-[11px] font-semibold text-red-600 dark:text-red-400 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-[#333333]">
                            <button type="button" onclick="closeCategoryModal()"
                                    class="px-4 py-2 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-[#333333] rounded-xl text-xs font-semibold hover:bg-neutral-50 dark:hover:bg-[#333333] transition">Batal</button>
                            <button type="submit" :disabled="! canSubmit()"
                                    class="px-5 py-2 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed">Simpan Kategori</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        function categoryPage(items, usage) {
            return {
                items: items,
                usage: usage,
                search: '',
                tab: 'all',
                name: @js(old('name', '')),
                type: @js(old('type', 'income')),

                countOf(type) {
                    return type === 'all'
                        ? (this.items.income || []).length + (this.items.expense || []).length
                        : (this.items[type] || []).length;
                },

                matches(type, name) {
                    if (this.tab !== 'all' && this.tab !== type) return false;
                    const query = this.search.trim().toLowerCase();
                    return query === '' || String(name).toLowerCase().includes(query);
                },

                visibleCount(type) {
                    return (this.items[type] || []).filter((name) => this.matches(type, name)).length;
                },

                resetFilters() {
                    this.search = '';
                    this.tab = 'all';
                },

                isDuplicate() {
                    const name = this.name.trim().toLowerCase();
                    if (name === '') return false;
                    return (this.items[this.type] || []).some((item) => String(item).trim().toLowerCase() === name);
                },

                canSubmit() {
                    return this.name.trim() !== '' && ! this.isDuplicate();
                },
            };
        }

        function openCategoryModal() {
            const modal = document.getElementById('categoryModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            const input = document.getElementById('category-name');
            if (input) window.setTimeout(() => input.focus(), 50);
        }

        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Gagal validasi (kategori bentrok / nama kosong) -> buka lagi modalnya
        // supaya isian user tidak hilang.
        @if ($errors->has('name') || $errors->has('type'))
            window.addEventListener('DOMContentLoaded', openCategoryModal);
        @endif
    </script>

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>
