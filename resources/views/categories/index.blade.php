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

        $itemsPayload = [
            'income' => array_column($income, 'name'),
            'expense' => array_column($expense, 'name'),
        ];

        // Data siap-edit untuk tiap kategori custom, dikirim ke Alpine
        // supaya form edit tidak perlu round-trip ke server.
        $editable = [];

        foreach (['income' => $income, 'expense' => $expense] as $type => $rows) {
            foreach ($rows as $row) {
                if ($row['is_global']) {
                    continue;
                }

                $editable[] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'type' => $type,
                    'color' => $row['color'],
                    'icon' => $row['icon'],
                    'used' => $usage[$row['name']] ?? 0,
                ];
            }
        }
    @endphp


    <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 sm:py-8"
         x-data="categoryPage(@js($itemsPayload), @js($usage), @js($editable), @js(\App\Support\CategoryStyle::COLORS), @js(\App\Support\CategoryStyle::ICONS))"
         @keydown.escape.window="if (deleteId !== null) { closeDelete() } else { closeModal() }">

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
            <button type="button" @click="openCreate()"
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
                        <span class="opacity-60" x-text="matchCount('{{ $filter['key'] }}')"></span>
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
                    class="group flex items-center gap-3 rounded-xl border border-neutral-200 dark:border-[#262626] px-3.5 py-3 hover:border-neutral-300 dark:hover:border-[#3f3f3f] hover:bg-neutral-50 dark:hover:bg-[#1c1c1c] transition-colors">
                    <span class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center {{ \App\Support\CategoryStyle::colorClasses($item['color']) }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! \App\Support\CategoryStyle::iconPath($item['icon']) !!}</svg>
                    </span>

                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-neutral-900 dark:text-neutral-50 leading-snug line-clamp-2" title="{{ $item['name'] }}">{{ $item['name'] }}</p>
                        @php($used = $usage[$item['name']] ?? 0)
                        @if ($used > 0)
                        {{-- Angka pemakaian dulu cuma teks mati. Sekarang link ke
                             /transactions yang sudah mendukung filter kategori. --}}
                        <a href="{{ route('transactions.index', ['category' => $item['name']]) }}"
                           class="mt-0.5 inline-flex items-center gap-1 text-[11px] text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-50 hover:underline underline-offset-2 transition"
                           title="Lihat {{ $used }} transaksi memakai {{ $item['name'] }}">
                            Dipakai di {{ $used }} transaksi
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        @else
                        <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-0.5">Belum pernah dipakai</p>
                        @endif
                    </div>

                    @if ($item['is_global'])
                    <span class="hidden sm:inline-flex shrink-0 self-start mt-0.5 text-[10px] font-bold uppercase tracking-wider text-neutral-400 dark:text-neutral-500 border border-neutral-200 dark:border-[#333333] rounded-md px-1.5 py-0.5"
                          title="Kategori bawaan — selalu tersedia, tidak bisa diubah atau dihapus">Bawaan</span>
                    @else
                    <span class="hidden sm:inline-flex shrink-0 self-start mt-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-500/10 border border-blue-200 dark:border-blue-500/20 rounded-md px-1.5 py-0.5"
                          title="Kategori buatanmu — bisa diubah atau dihapus">Custom</span>
                    @endif

                    @unless ($isDemo)
                    {{-- Aksi ubah & hapus muncul saat hover/fokus supaya kartu yang
                         cuma dibaca tetap bersih. Pakai opacity (bukan hidden)
                         supaya tetap bisa dijangkau keyboard & screen reader. --}}
                    <div class="shrink-0 flex items-center gap-0.5 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                        @unless ($item['is_global'])
                        <button type="button" @click='editCategory(@js($item['id']))'
                                class="w-8 h-8 flex items-center justify-center text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-50 hover:bg-neutral-100 dark:hover:bg-[#262626] rounded-lg transition"
                                title="Ubah kategori {{ $item['name'] }}" aria-label="Ubah kategori {{ $item['name'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        {{-- Sengaja BUKAN <form> di sini. Kalau formnya ada,
                             klik akan tetap terkirim ke server begitu JS gagal
                             dimuat — menghapus kategori tanpa konfirmasi sama
                             sekali. Tombol mati lebih baik daripada diam-diam
                             menghapus. Pengiriman yang sungguhan dilakukan
                             oleh form tersembunyi #deleteForm. --}}
                        <button type="button" @click="askDelete(@js($item['id']), @js($item['name']), {{ $used }})"
                                class="w-8 h-8 flex items-center justify-center text-neutral-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition"
                                title="Hapus kategori {{ $item['name'] }}" aria-label="Hapus kategori {{ $item['name'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a2 2 0 00-1-1h-4a2 2 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                        @endunless
                    </div>
                    @endunless
                </li>
                @endforeach
            </ul>
        </section>
        @endforeach

        {{-- Satu empty state untuk dua seksi sekaligus. Sebelumnya tiap seksi
             punya blok kosong sendiri, jadi mengetik kata kunci yang tidak cocok
             memunculkan dua kartu kosong identik bertumpuk dan halaman terlihat
             rusak. --}}
        <div class="bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] rounded-2xl shadow-sm px-4 sm:px-5 py-10 text-center mb-5"
             x-show="totalVisible() === 0" x-cloak>
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

        <p class="text-[11px] text-neutral-400 dark:text-neutral-500 text-center mt-6 px-4 leading-relaxed">
            Kategori bawaan selalu tersedia dan tidak bisa dihapus. Menghapus kategori custom
            tidak menghapus riwayat — kategori lama tetap tersimpan pada tiap transaksi.
        </p>

        {{-- Modal tambah / ubah kategori.
             Satu form untuk dua keperluan: aksi "Tambah" buka dalam mode
             tambah (action = store), tombol ubah di tiap kartu membuka
             mode ubah (action = update ke kategori itu). --}}
        <div id="categoryModal" class="fixed inset-0 z-[60] hidden overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <div class="fixed inset-0 bg-neutral-900/60 dark:bg-black/70 backdrop-blur-sm" @click="closeModal()"></div>

            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-left shadow-sm w-full sm:max-w-md">

                    <div class="flex items-center justify-between p-6 border-b border-neutral-200 dark:border-[#333333]">
                        <div class="flex items-center gap-3">
                            {{-- Pratinjau langsung warna + ikon yang dipilih, supaya
                                ikumu jadi terasa sebelum disimpan. --}}
                            <span class="p-2.5 rounded-xl shrink-0" :class="appearanceClass()">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path :d="iconPath()"/></svg>
                            </span>
                            <div>
                                <h3 id="category-modal-title" class="text-base font-bold text-neutral-900 dark:text-neutral-50" x-text="isEditing() ? 'Ubah Kategori' : 'Tambah Kategori'"></h3>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400" x-text="isEditing() ? 'Transaksi & anggaran lama ikut mengikuti perubahan ini.' : 'Langsung tersedia di form transaksi & asisten AI'"></p>
                            </div>
                        </div>
                        <button type="button" @click="closeModal()" aria-label="Tutup"
                                class="text-neutral-400 hover:text-neutral-900 dark:hover:text-neutral-100 p-1 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form :action="formAction()" method="POST" class="p-6 space-y-4">
                        @csrf
                        <template x-if="isEditing()"><input type="hidden" name="_method" value="PATCH"></template>
                        <input type="hidden" name="editing_id" :value="editingId">

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
                                    <input type="radio" name="type" value="{{ $value }}" x-model="type" class="sr-only" required>
                                    <span class="text-xs sm:text-sm font-bold">{{ $label }}</span>
                                </label>
                                @endforeach
                            </div>
                            @error('type')
                                <p class="text-[11px] font-semibold text-red-600 dark:text-red-400 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Warna & ikon --}}
                        <div>
                            <span class="block text-xs font-semibold text-neutral-600 dark:text-neutral-300 mb-2">Warna</span>
                            <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Warna kategori">
                                <template x-for="key in colorKeys" :key="key">
                                    <button type="button" @click="color = key" :aria-pressed="color === key" role="radio"
                                            :aria-label="'Warna ' + key"
                                            :title="key"
                                            class="w-7 h-7 rounded-lg flex items-center justify-center transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100"
                                            :class="[palette[key] || '', color === key ? 'ring-2 ring-neutral-900 dark:ring-neutral-50 ring-offset-2 dark:ring-offset-[#171717]' : '']">
                                        <svg class="w-3.5 h-3.5" x-show="color === key" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </template>
                            </div>
                            <input type="hidden" name="color" :value="color">
                        </div>

                        <div>
                            <span class="block text-xs font-semibold text-neutral-600 dark:text-neutral-300 mb-2">Ikon</span>
                            <div class="grid grid-cols-8 gap-1 max-h-32 overflow-y-auto p-1 -m-1" role="radiogroup" aria-label="Ikon kategori">
                                <template x-for="key in iconKeys" :key="key">
                                    <button type="button" @click="icon = key" :aria-pressed="icon === key" role="radio"
                                            :aria-label="'Ikon ' + key" :title="key"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-neutral-900 dark:focus-visible:ring-neutral-100"
                                            :class="icon === key
                                                ? 'bg-neutral-900 text-white dark:bg-neutral-100 dark:text-neutral-900'
                                                : 'text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-[#262626]'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path :d="iconSet[key] || iconSet.defaultIcon"/></svg>
                                    </button>
                                </template>
                            </div>
                            <input type="hidden" name="icon" :value="icon">
                        </div>

                        {{-- Peringatan kalau ganti jenis akan ikut memindahkan
                             transaksi — ini keputusan yang cukup besar, jadi
                            harus terlihat, bukan diam-diam terjadi. --}}
                        <div x-show="retypeWarning()" x-cloak
                             class="flex items-start gap-2.5 rounded-xl border border-amber-200 dark:border-amber-500/30 bg-amber-50 dark:bg-amber-500/10 p-3">
                            <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                            <p class="text-[11px] font-semibold text-amber-800 dark:text-amber-300">
                                <span x-text="editingUsed > 0 ? editingUsed + ' transaksi ikut dipindahkan jenisnya.' : 'Jenis kategori akan berubah.'"></span>
                                Riwayat nominalnya tetap sama.
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-neutral-200 dark:border-[#333333]">
                            <button type="button" @click="closeModal()"
                                    class="px-4 py-2 bg-white dark:bg-[#262626] text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-[#333333] rounded-xl text-xs font-semibold hover:bg-neutral-50 dark:hover:bg-[#333333] transition">Batal</button>
                            <button type="submit" :disabled="! canSubmit()"
                                    class="px-5 py-2 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-xl text-xs font-semibold shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed"
                                    x-text="isEditing() ? 'Simpan Perubahan' : 'Simpan Kategori'"></button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        {{-- Dialog konfirmasi hapus. Mengganti confirm() bawaan browser yang
             tampilannya beda jauh dari sisa UI (dan tidak bisa di-style). --}}
        <div id="deleteModal" class="fixed inset-0 z-[70] hidden overflow-y-auto" role="alertdialog" aria-modal="true" aria-labelledby="delete-modal-title">
            <div class="fixed inset-0 bg-neutral-900/60 dark:bg-black/70 backdrop-blur-sm" @click="closeDelete()"></div>
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative w-full sm:max-w-sm rounded-2xl bg-white dark:bg-[#171717] border border-neutral-200 dark:border-[#333333] text-left shadow-sm overflow-hidden">
                    <div class="p-6">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a2 2 0 00-1-1h-4a2 2 0 00-1 1v3M4 7h16"/></svg>
                        </span>
                        <h3 id="delete-modal-title" class="text-base font-bold text-neutral-900 dark:text-neutral-50 mt-4">Hapus kategori ini?</h3>
                        <p class="text-xs text-neutral-600 dark:text-neutral-400 mt-2 leading-relaxed">
                            <span class="font-semibold text-neutral-900 dark:text-neutral-50" x-text="deleteName"></span>
                            akan hilang dari pilihan di form transaksi.
                        </p>
                        <p class="text-[11px] text-neutral-500 dark:text-neutral-400 mt-2 leading-relaxed">
                            <span x-show="deleteUsed > 0"
                                  x-text="'Transaksi yang sudah tercatat (' + deleteUsed + ') tetap utuh — kategorinya tersimpan di riwayat, hanya pilihannya yang hilang.'"></span>
                            <span x-show="deleteUsed === 0">Kategori ini belum dipakai transaksi apa pun, jadi tidak ada riwayat yang tersentuh.</span>
                        </p>
                    </div>
                    <div class="flex items-center justify-end gap-3 px-6 py-4 bg-neutral-50 dark:bg-[#1c1c1c] border-t border-neutral-200 dark:border-[#333333]">
                        <button type="button" @click="closeDelete()"
                                class="px-4 py-2 bg-white dark:bg-[#171717] text-neutral-700 dark:text-neutral-200 border border-neutral-300 dark:border-[#333333] rounded-xl text-xs font-semibold hover:bg-neutral-100 dark:hover:bg-[#333333] transition">Batal</button>
                        <button type="button" @click="confirmDelete()"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-500 text-white rounded-xl text-xs font-semibold shadow-sm transition">Ya, hapus</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function categoryPage(items, usage, editable, palette, iconSet) {
            return {
                items: items,
                usage: usage,
                editable: editable,
                palette: palette,
                iconSet: iconSet,
                colorKeys: Object.keys(palette),
                iconKeys: Object.keys(iconSet),

                search: '',
                tab: 'all',

                // state form
                mode: @js(old('editing_id') ? 'edit' : 'create'),
                editingId: @js(old('editing_id')),
                editingOriginalType: @js(old('type', 'income')),
                editingUsed: 0,
                name: @js(old('name', '')),
                type: @js(old('type', 'income')),
                color: @js(old('color', 'green')),
                icon: @js(old('icon', 'tag')),

                // state dialog hapus
                deleteId: null,
                deleteName: '',
                deleteUsed: 0,

                // Server menolak simpan (mis. nama bentrok) -&gt; buka lagi
                // modalnya dengan isian user tetap utuh, jangan dibuang.
                init() {
                    if (this.mode === 'edit') {
                        this.openModal();
                    }
                },

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

                // Jumlah yang benar-benar terlihat di layar, untuk empty state
                // global (dua seksi bisa kosong bareng).
                totalVisible() {
                    return this.visibleCount('income') + this.visibleCount('expense');
                },

                // Angka di tombol filter. Sebelumnya pakai countOf() yang
                // mengabaikan search, sementara badge header pakai
                // visibleCount() yang ikut — jadi dua angka di satu layar
                // artinya beda. Sekarang keduanya sama.
                matchCount(key) {
                    if (key === 'all') return this.totalVisible();
                    return this.visibleCount(key);
                },

                resetFilters() {
                    this.search = '';
                    this.tab = 'all';
                },

                // ---- form ----
                isEditing() {
                    return this.mode === 'edit';
                },

                formAction() {
                    return this.isEditing()
                        ? @js(route('categories.update', ['category' => 0])).replace(/\/0$/, '/' + this.editingId)
                        : @js(route('categories.store'));
                },

                openCreate() {
                    this.mode = 'create';
                    this.editingId = null;
                    this.editingUsed = 0;
                    this.name = '';
                    this.type = 'income';
                    this.color = 'green';
                    this.icon = 'tag';
                    this.openModal();
                },

                editCategory(id) {
                    const item = this.editable.find((row) => row.id === id);
                    if (!item) return;

                    this.mode = 'edit';
                    this.editingId = item.id;
                    this.editingOriginalType = item.type;
                    this.editingUsed = item.used;
                    this.name = item.name;
                    this.type = item.type;
                    this.color = item.color;
                    this.icon = item.icon;
                    this.openModal();
                },

                openModal() {
                    const modal = document.getElementById('categoryModal');
                    if (!modal) return;
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                    const input = document.getElementById('category-name');
                    if (input) window.setTimeout(() => { input.focus(); input.select(); }, 50);
                },

                closeModal() {
                    const modal = document.getElementById('categoryModal');
                    if (!modal) return;
                    modal.classList.add('hidden');
                    document.body.style.overflow = '';
                },

                appearanceClass() {
                    return this.palette[this.color] || this.palette.neutral || '';
                },

                iconPath() {
                    return this.iconSet[this.icon] || this.iconSet.tag || '';
                },

                // Peringatan "ikut berubah jenisnya" hanya relevan saat
                // kategori yang dipakai transaksi benar-benar dipindah jenisnya.
                retypeWarning() {
                    return this.isEditing() && this.type !== this.editingOriginalType;
                },

                // Mode edit: kategori yang sedang diedit tidak boleh
                // dianggap bentrok dengan dirinya sendiri.
                isDuplicate() {
                    const name = this.name.trim().toLowerCase();
                    if (name === '') return false;
                    return (this.items[this.type] || []).some((item) => String(item).trim().toLowerCase() === name);
                },

                canSubmit() {
                    return this.name.trim() !== '' && ! this.isDuplicate();
                },

                // ---- hapus ----
                askDelete(id, name, used) {
                    this.deleteId = id;
                    this.deleteName = name;
                    this.deleteUsed = used;
                    const modal = document.getElementById('deleteModal');
                    if (!modal) return;
                    modal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';
                },

                closeDelete() {
                    const modal = document.getElementById('deleteModal');
                    if (!modal) return;
                    modal.classList.add('hidden');
                    this.deleteId = null;
                    if (document.getElementById('categoryModal').classList.contains('hidden')) {
                        document.body.style.overflow = '';
                    }
                },

                confirmDelete() {
                    if (this.deleteId === null) return;
                    const form = document.getElementById('deleteForm');
                    if (!form) return;
                    form.action = @js(route('categories.destroy', ['category' => 0])).replace(/\/0$/, '/' + this.deleteId);
                    form.submit();
                },
            };
        }
    </script>

    {{-- Form hapus yang dipakai dialog konfirmasi. Satu form, action diisi
         Alpine saat konfirmasi ditekan, lalu di-submit. --}}
    <form id="deleteForm" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>

    @include('components.export-modal')

@auth
@include('components.ai-chat')
@endauth

</body>
</html>
