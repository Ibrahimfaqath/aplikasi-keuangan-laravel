<!DOCTYPE html>
<html lang="id" class="h-full">

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
    <meta property="og:description" content="Transaksi yang sudah dihapus masih bisa dipulihkan sebelum dihapus permanen.">
    <meta property="og:url" content="{{ url()->current() }}">
    <title>Sampah - dompetku</title>

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { overflow-x: hidden; }

        /* Latar thumbnail bukti memakai token surface daisyUI yang sedang
           aktif, bukan hex per mode. Nilainya diambil dari custom property
           daisyUI (--b2) di dalam blok :root, sehingga ikut berubah sendiri
           saat `data-theme` di <html> berganti — lihat applyTheme() di app.js.
           `var(--fallback-b2, ...)` mengikuti urutan yang sama seperti yang
           ditulis komponen daisyUI, supaya tetap sintaksis valid di
           tema yang menyetelnya. */
        :root {
            --trash-thumb-bg: var(--fallback-b2, oklch(var(--b2)));
        }
        img[loading="lazy"] { background: var(--trash-thumb-bg); }
    </style>
</head>

<body class="app-shell-content min-h-full font-sans antialiased text-base-content flex flex-col"
      x-data="trashPage()">

    {{-- Alur konfirmasi hapus permanen. confirm() native tidak bisa
         di-style dan tidak pernah menyebut transaksi mana yang dihapus --
         padahal ini satu-satunya aksi di aplikasi yang benar-benar menghapus
         data. `focusable` supaya fokus mendarat di tombol Batal, bukan aksi
         destruktif: yang tidak sengaja menekan Enter akan membatalkan. --}}
    <x-modal name="konfirmasi-hapus-permanen" maxWidth="md" focusable>
        <form method="POST" x-bind:action="forceTarget ? forceTarget.url : '#'">
            @csrf
            @method('DELETE')

            <div class="p-5 sm:p-6">
                <div class="w-11 h-11 rounded-box bg-error/10 text-error border border-error/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </div>

                <h2 class="mt-4 text-base font-bold tracking-tight text-base-content">
                    Hapus permanen transaksi ini?
                </h2>
                <p class="mt-1.5 text-xs text-base-content/60">
                    Tindakan ini tidak bisa dibatalkan — transaksi dan bukti fotonya langsung hilang dari Sampah.
                </p>

                <div class="mt-4 p-3 rounded-box bg-base-200 border border-base-300">
                    <p class="font-semibold text-sm text-base-content truncate" x-text="forceTarget ? forceTarget.title : ''"></p>
                    {{-- Kelas di :class tetap LITERAL per cabang. Kalau dirangkai,
                         Tailwind tidak pernah memindainya dan warnanya hilang. --}}
                    <p class="mt-0.5 text-sm font-extrabold tabular-nums"
                       :class="forceTarget && forceTarget.isIncome ? 'text-success' : 'text-error'"
                       x-text="forceTarget ? forceTarget.amount : ''"></p>
                </div>

                <p class="mt-3 text-[11px] text-base-content/50">
                    Kalau ternyata tidak sengaja, mending <span class="font-semibold text-base-content/70">Pulihkan</span> &mdash; datamu tetap ada di Sampah.
                </p>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 sm:px-6 py-4 border-t border-base-300 bg-base-200">
                <button type="button" x-on:click="$dispatch('close-modal', 'konfirmasi-hapus-permanen')"
                        class="btn btn-outline btn-sm">
                    Batal
                </button>
                <button type="submit"
                        class="btn btn-error btn-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    Hapus permanen
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Hapus terpilih. Satu konfirmasi sudah cukup karena jangkauan dibatasi
         oleh apa yang dicentang user sendiri -- tidak seperti Kosongkan Sampah. --}}
    <x-modal name="konfirmasi-hapus-terpilih" maxWidth="md" focusable>
        <form method="POST" action="{{ route('transactions.bulk-destroy') }}">
            @csrf
            <input type="hidden" name="ids" :value="selection.join(',')">

            <div class="p-5 sm:p-6">
                <div class="w-11 h-11 rounded-box bg-error/10 text-error border border-error/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </div>

                <h2 class="mt-4 text-base font-bold tracking-tight text-base-content">
                    Hapus <span x-text="selection.length" class="tabular-nums"></span> transaksi permanen?
                </h2>
                <p class="mt-1.5 text-xs text-base-content/60">
                    Transaksi dan bukti fotonya langsung hilang dari Sampah, dan tidak bisa dipulihkan lagi.
                </p>

                <div class="mt-4 max-h-40 overflow-y-auto rounded-box bg-base-200 border border-base-300 divide-y divide-base-300">
                    <template x-for="item in selectedItems" :key="item.id">
                        <div class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="text-xs font-medium text-base-content/70 truncate" x-text="item.title"></span>
                            <span class="text-xs font-bold tabular-nums shrink-0" :class="item.isIncome ? 'text-success' : 'text-error'" x-text="item.amount"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-5 sm:px-6 py-4 border-t border-base-300 bg-base-200">
                <button type="button" x-on:click="$dispatch('close-modal', 'konfirmasi-hapus-terpilih')"
                        class="btn btn-outline btn-sm">
                    Batal
                </button>
                <button type="submit"
                        class="btn btn-error btn-sm">
                    Hapus permanen
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Kosongkan Sampah. Jangkauan ditentukan server, bukan user: satu klik
         salah berarti kehilangan SEMUA isi Sampah. Karena itu tombolnya hanya
         hidup setelah kata kunci diketik ulang -- pola yang dipakai GitHub dan
         Vercel untuk aksi yang benar-benar tidak bisa dibatalkan. --}}
    <x-modal name="kosongkan-sampah" maxWidth="md">
        <form method="POST" action="{{ route('transactions.empty-trash') }}">
            @csrf

            <div class="p-5 sm:p-6">
                <div class="w-11 h-11 rounded-box bg-error/10 text-error border border-error/20 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </div>

                <h2 class="mt-4 text-base font-bold tracking-tight text-base-content">Kosongkan Sampah?</h2>
                <p class="mt-1.5 text-xs text-base-content/60">
                    <span class="font-bold text-base-content/70">{{ $transactions->total() }}</span>
                    transaksi akan dihapus permanen beserta bukti fotonya. Tidak ada yang bisa dipulihkan setelahnya.
                </p>

                <label for="emptyTrashConfirm" class="block mt-4 text-xs font-semibold text-base-content/70">
                    Ketik <span class="font-extrabold text-error">{{ \App\Http\Controllers\TransactionController::EMPTY_TRASH_CONFIRMATION }}</span> untuk melanjutkan
                </label>
                {{-- placeholder ditulis sebagai atribut Blade biasa, bukan
                     `:placeholder` — yang itu dievaluasi Alpine sebagai JS dan
                     akan salah. Nilai konfirmasi masuk lewat x-model. --}}
                <input type="text" id="emptyTrashConfirm" x-model="emptyTrashConfirm" autocomplete="off"
                       spellcheck="false" autocapitalize="characters"
                       placeholder="{{ \App\Http\Controllers\TransactionController::EMPTY_TRASH_CONFIRMATION }}"
                       class="input input-bordered w-full mt-1.5">
            </div>

            <div class="flex items-center justify-end gap-2 px-5 sm:px-6 py-4 border-t border-base-300 bg-base-200">
                <button type="button" x-on:click="$dispatch('close-modal', 'kosongkan-sampah'); emptyTrashConfirm = ''"
                        class="btn btn-outline btn-sm">
                    Batal
                </button>
                <button type="submit" :disabled="emptyTrashConfirm !== emptyTrashKeyword"
                        class="btn btn-error btn-sm disabled:opacity-40">
                    Kosongkan Sampah
                </button>
            </div>
        </form>
    </x-modal>

    <x-sidebar title="Sampah" :back="route('transactions.index')" minimal />

    {{-- Notifikasi. Slot `undo` hanya dirender kalau flash 'undo_restore' ada:
         pemulihan adalah aksi yang bisa dibatalkan, jadi jalur pembatalannya
         harus ikut tampil. Tanpa itu, user yang salah klik perlu 3 langkah lagi
         hanya untuk membatalkan 1 klik. Isi slot: submit balik ke Sampah —
         logikanya berbalik dengan pemulihan, jadi bunyinya menjelaskan itu,
         bukan sekadar "Undo" yang kabur. --}}
    <x-flash :undo-id="session('undo_restore')['id'] ?? null">
        {{-- Isi slot TIDAK bisa memakai variabel lokal halaman (mis. $undoId):
             Blade mengekstrak isi slot ke closure terpisah sehingga scope-nya
             terpisah. Karena itu nilainya diambil lagi dari session di sini —
             dengan `?? null` supaya tidak error saat flash-nya kosong.
             Muncul/tidaknya area undo tetap dikendalikan :undo-id di atas. --}}
        <x-slot:undo>
            @if (session('undo_restore')['id'] ?? null)
                <form action="{{ route('transactions.destroy', session('undo_restore')['id']) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h11a5 5 0 010 10h-4M3 10l4-4m-4 4l4 4" />
                        </svg>
                        Urungkan
                    </button>
                </form>
                <span class="text-[11px] opacity-70">kembalikan ke Sampah</span>
            @endif
        </x-slot:undo>
    </x-flash>

    {{-- $hasActiveFilters dihitung di controller (TransactionController::trashed),
         supaya partial yang di-render ulang lewat ?partial=1 memakai definisi
         yang sama persis, bukan implementasi kedua di view. --}}
    <div class="flex-1 w-full min-w-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 pb-8 space-y-6 sm:space-y-6 overflow-x-hidden">

        <section class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-5">
            <div class="flex items-start gap-3">
                <div class="p-2.5 bg-error/10 text-error border border-error/20 rounded-box flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm font-bold tracking-tight text-base-content">Transaksi yang sudah dihapus</h2>
                    <p class="mt-1 text-xs text-base-content/60">
                        Semuanya masih utuh dan bisa dipulihkan. Hanya <span class="font-semibold text-base-content/70">Hapus permanen</span> yang benar-benar menghapus datanya, dan itu tidak bisa dibatalkan.
                    </p>
                </div>
            </div>

            {{-- Tiga angka, satu skala. Sebelumnya ada "Total Nominal" yang
                 menjumlahkan pemasukan + pengeluaran — di aplikasi keuangan
                 angka itu tidak punya arti apa pun, sementara pengeluaran
                 sendiri justru tidak pernah ditampilkan.

                 `stats`/`stat` untuk triplet label/angka/catatan; `bg-transparent`
                 supaya tile tidak menimpa bg-base-100 kartu induk (lihat juga
                 resources/views/budgets/index.blade.php). --}}
            <div class="stats stats-vertical sm:stats-horizontal w-full grid grid-cols-1 sm:grid-cols-3 bg-transparent mt-5 pt-5 border-t border-base-300">
                @php
                    $statClass = 'stat-value mt-1 truncate text-lg sm:text-xl font-extrabold tracking-tight tabular-nums';
                    $statLabel = 'stat-title text-[10px] sm:text-[11px] font-semibold uppercase tracking-wider text-base-content/60';
                @endphp
                <div class="stat min-w-0 py-4 px-0 sm:px-4 sm:py-0">
                    <p class="{{ $statLabel }}">Di Sampah</p>
                    <p class="{{ $statClass }} text-base-content">{{ $transactions->total() }} transaksi</p>
                    @if ($hasActiveFilters)
                        <p class="stat-desc mt-0.5 text-[11px] text-base-content/50">sesuai filter aktif</p>
                    @endif
                </div>
                <div class="stat min-w-0 py-4 px-0 sm:px-4 sm:py-0">
                    <p class="{{ $statLabel }}">Pengeluaran</p>
                    <p class="{{ $statClass }} text-error privacy-target"
                       data-amount="{{ \App\Services\AmountFormatter::compact($totalExpense) }}">{{ \App\Services\AmountFormatter::compact($totalExpense) }}</p>
                </div>
                <div class="stat min-w-0 py-4 px-0 sm:px-4 sm:py-0">
                    <p class="{{ $statLabel }}">Pemasukan</p>
                    <p class="{{ $statClass }} text-success privacy-target"
                       data-amount="{{ \App\Services\AmountFormatter::compact($totalIncome) }}">{{ \App\Services\AmountFormatter::compact($totalIncome) }}</p>
                </div>
            </div>
        </section>

        {{-- ID di sini sengaja sama persis dengan halaman /transactions
             (filterForm, filterSearch, filterCategory, filterPeriod,
             filterReset, riwayat, riwayatTable, riwayatCount): mesin filter
             di resources/js/app.js jadi satu untuk kedua halaman, bukan dua
             implementasi yang harus dijaga sinkron. --}}
        <section class="card bg-base-100 border border-base-300 shadow-sm p-4 sm:p-5">
            <form id="filterForm" method="GET" action="{{ route('transactions.trashed') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                {{-- Id (`filterSearch`, `filterCategory`, `filterPeriod`,
                     `filterReset`, `filterStatus`, `filterForm`) WAJIB sama persis
                     dengan halaman /transactions: mesin filter di
                     resources/js/app.js membacanya dengan document.getElementById. --}}
                <div class="lg:col-span-3 relative">
                    <label for="filterSearch" class="sr-only">Cari transaksi di Sampah</label>
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-base-content/40">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" id="filterSearch" name="search" value="{{ request('search') }}" placeholder="Cari di Sampah..." autocomplete="off"
                           class="input input-bordered w-full pl-11 pr-4 text-xs sm:text-sm placeholder:text-base-content/40">
                </div>

                {{-- Tipe memakai segoup radio asli, sama seperti di /transactions:
                     satu klik, panah kiri/kanan langsung memindah pilihan, tetap
                     ikut submit kalau JS mati.

                     Dibungkus `tabs tabs-boxed` seperti di /categories: komponennya
                     yang menata latar, radius, dan padding. State aktif diisin
                     `has-[:checked]:` karena input-nya tetap sr-only. --}}
                <div class="lg:col-span-4">
                    <fieldset>
                        <legend class="sr-only">Filter berdasarkan tipe transaksi</legend>
                        <div class="tabs tabs-boxed w-full" role="group">
                            @php
                                $typeFilters = ['' => 'Semua', 'income' => 'Pemasukan', 'expense' => 'Pengeluaran'];
                                $currentType = (string) request('type', '');
                            @endphp
                            @foreach ($typeFilters as $typeValue => $typeLabel)
                                {{-- Tiap pasangan input+label dibungkus <label> sendiri;
                                     kalau tidak, `has-[:checked]:` akan menyalakan
                                     lebih dari satu tab sekaligus. --}}
                                @php $typeId = 'trashType'.($typeValue === '' ? 'All' : ucfirst($typeValue)); @endphp
                                <label for="{{ $typeId }}"
                                       class="tab px-1.5 sm:px-2 py-2 text-center text-xs sm:text-sm font-semibold cursor-pointer truncate text-base-content/60 has-[:checked]:bg-base-100 has-[:checked]:text-base-content has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-base-content">
                                    <input type="radio" name="type" id="{{ $typeId }}" value="{{ $typeValue }}"
                                           class="sr-only" @checked($currentType === (string) $typeValue)
                                           onchange="applyFilters()">
                                    {{ $typeLabel }}
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>

                <div class="lg:col-span-2">
                    @php
                        $categoryFilterOptions = ['' => 'Semua Kategori'];
                        foreach (\App\Models\Category::allNames(auth()->id()) as $cat) { $categoryFilterOptions[$cat] = $cat; }
                    @endphp
                    <label for="filterCategory" class="sr-only">Filter berdasarkan kategori</label>
                    <select id="filterCategory" name="category" onchange="applyFilters()"
                            class="select select-bordered select-sm w-full">
                        @foreach ($categoryFilterOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('category', '') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Di halaman Sampah, "periode" berarti kapan dibuang, bukan kapan
                     transaksinya terjadi — lihat TransactionController::trashed(). --}}
                <div class="lg:col-span-2">
                    <label for="filterPeriod" class="sr-only">Filter berdasarkan kapan transaksi dihapus</label>
                    <select id="filterPeriod" name="period" onchange="applyFilters()"
                            class="select select-bordered select-sm w-full">
                            <option value="{{ 'all' }}" @selected(request('period', 'all') === (string) 'all')>{{ 'Semua Waktu' }}</option>
                            <option value="{{ 'today' }}" @selected(request('period', 'all') === (string) 'today')>{{ 'Hari Ini' }}</option>
                            <option value="{{ '7_days' }}" @selected(request('period', 'all') === (string) '7_days')>{{ '7 Hari Terakhir' }}</option>
                            <option value="{{ 'this_month' }}" @selected(request('period', 'all') === (string) 'this_month')>{{ 'Bulan Ini' }}</option>
                    </select>
                </div>

                {{-- Kolom harus berjumlah pas 12, kalau tidak tombol reset terdorong
                     ke baris sendiri: 3 (cari) + 4 (tipe) + 2 (kategori) + 2 (periode) + 1 (reset).

                     `btn-sm` menurunkan tinggi ke 2rem dan
                     `.btn-square:where(.btn-sm)` menjaga sisi tetap sama, jadi
                     tombol reset tetap Kotak dan tidak ikut meninggi. --}}
                <div class="lg:col-span-1 flex gap-2 items-center justify-end">
                    <a id="filterReset" href="{{ route('transactions.trashed') }}" title="Reset filter" aria-label="Reset semua filter"
                       class="btn btn-sm btn-square shrink-0 text-base-content/60 {{ $hasActiveFilters ? '' : 'hidden' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                    <p id="filterStatus" role="status" aria-live="polite" class="sr-only"></p>
                </div>
            </form>
        </section>

        <section id="riwayat" class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-4 sm:px-5 py-4 border-b border-base-300">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold tracking-tight text-base-content">Transaksi di Sampah</h2>
                    {{-- Suffix-nya CUSTOM karena kalimat dashboard ("N transaksi
                         tercatat") tidak berlaku di sini. --}}
                    <p id="riwayatCount" data-count-suffix=" item di Sampah"
                       class="text-xs text-base-content/60 mt-0.5">{{ $transactions->total() ?? 0 }} item di Sampah</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @unless ($trashLocked)
                        {{-- Satu-satunya aksi tanpa pilih-items, jadi yang paling
                             berbahaya. Tombolnya sengaja dibuat kecil dan redup. --}}
                        <button type="button" @click="openEmptyTrash()"
                                class="btn btn-ghost btn-sm text-base-content/40 hover:bg-error/10 hover:text-error">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2.25 2.25 0 0116.138 21H7.862a2.25 2.25 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Kosongkan
                        </button>
                    @endunless
                    <a href="{{ route('transactions.index') }}"
                       class="btn btn-ghost btn-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                        <span class="hidden sm:inline">Kembali</span>
                    </a>
                </div>
            </div>

            {{-- Toolbar bulk. Muncul hanya saat ada yang dicentang, jadi tombol
                 destruktif tidak pernah tampil sebagai pilihan default.
                 x-cloak menyembunyikannya sebelum Alpine sempat jalan --
                 tanpa itu, form-nya akan kelihatan tapi tidak bekerja.

                 `alert` dipinjam untuk bentuk/radius-nya; tata letak tetap baris
                 (utility `flex` menimpa grid bawaan daisyUI). Warnanya netral
                 penuh lewat `neutral`, yang dalam tema monokrom ini yang paling
                 kontras untuk teks putih. --}}
            <div x-cloak x-show="selection.length > 0"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="alert flex flex-wrap items-center justify-between gap-3 px-4 sm:px-5 py-3 bg-base-content text-base-100">
                <p class="text-xs font-semibold" role="status" aria-live="polite">
                    <span x-text="selection.length"></span> dipilih
                    @if (($transactions->total() ?? 0) > 10)
                        <span class="font-normal opacity-70">&mdash; pilihan berlaku untuk halaman ini saja</span>
                    @endif
                </p>

                <div class="flex items-center gap-2">
                    <button type="button" @click="clearSelection()"
                            class="btn btn-ghost btn-sm text-base-content/70 hover:text-base-content">
                        Batal pilih
                    </button>

                    @unless ($trashLocked)
                        {{-- Dua form terpisah, bukan satu: form pertama tidak
                             boleh submitserver-side kalau yang kedua yang
                             benar-benar destructive. --}}
                        <form method="POST" action="{{ route('transactions.bulk-restore') }}">
                            @csrf
                            @include('transactions.partials.trash-ids')
                            <button type="submit" class="btn btn-sm btn-neutral">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                Pulihkan
                            </button>
                        </form>
                        <button type="button" @click="askBulkDestroy()"
                                class="btn btn-sm btn-error">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            Hapus permanen
                        </button>
                    @endunless
                </div>
            </div>

            <div id="riwayatTable" aria-busy="false">
                @include('transactions.partials.trash-table', [
                    'transactions' => $transactions,
                    'hasActiveFilters' => $hasActiveFilters,
                ])
            </div>

        </section>

    </div>

    <script>
        function trashPage() {
            return {
                // -------------------------------------------------------------
                // Hapus permanen satu transaksi.
                // Menggantikan confirm() native: modalnya menyebut judul +
                // nominal, jadi user tahu persis apa yang sedang hilang.
                // Tombol Hapus di baris cuma mengisi state ini lalu membuka
                // modal — tidak ada form per baris yang perlu "menonaktifkan
                // dirinya" saat dibatalkan.
                // -------------------------------------------------------------
                forceTarget: null,

                askForceDestroy(el) {
                    const amount = el.dataset.amount || '';
                    this.forceTarget = {
                        url: el.dataset.url,
                        title: el.dataset.title || '',
                        amount: amount,
                        isIncome: amount.trim().charAt(0) === '+',
                    };
                    this.$dispatch('open-modal', 'konfirmasi-hapus-permanen');
                },

                // -------------------------------------------------------------
                // Pilihan massal.
                // -------------------------------------------------------------
                selection: [],
                emptyTrashConfirm: '',
                emptyTrashKeyword: @js(\App\Http\Controllers\TransactionController::EMPTY_TRASH_CONFIRMATION),

                isSelected(id) {
                    return this.selection.some((v) => String(v) === String(id));
                },

                toggleSelection(id) {
                    const i = this.selection.findIndex((v) => String(v) === String(id));
                    if (i === -1) this.selection.push(String(id));
                    else this.selection.splice(i, 1);
                },

                clearSelection() {
                    this.selection = [];
                },

                // Checkbox "pilih semua" punya tiga keadaan, dan `indeterminate`
                // tidak punya padanan HTML -- jadi harus diset lewat atribut.
                get selectableIds() {
                    return [...new Set(
                        [...document.querySelectorAll('#riwayatTable input[type="checkbox"][name="ids[]"]')]
                            .map((el) => String(el.value))
                    )];
                },
                get allSelected() {
                    const ids = this.selectableIds;
                    return ids.length > 0 && this.selection.length === ids.length;
                },
                set allSelected(on) {
                    this.selection = on ? this.selectableIds : [];
                },
                get someSelected() {
                    return this.selection.length > 0 && !this.allSelected;
                },

                // Ringkasan item terpilih untuk modal konfirmasi. Diambil dari
                // DOM karena isinya ikut berubah setiap partial fetch.
                get selectedItems() {
                    return [...document.querySelectorAll('#riwayatTable tr[data-trash-item]')]
                        .filter((tr) => {
                            const cb = tr.querySelector('input[type="checkbox"]');
                            return cb && this.selection.some((v) => String(v) === cb.value);
                        })
                        .map((tr) => ({
                            title: tr.dataset.trashTitle || '',
                            amount: tr.dataset.trashAmount || '',
                            isIncome: (tr.dataset.trashAmount || '').trim().charAt(0) === '+',
                        }));
                },

                askBulkDestroy() {
                    if (this.selection.length === 0) return;
                    this.$dispatch('open-modal', 'konfirmasi-hapus-terpilih');
                },

                openEmptyTrash() {
                    this.emptyTrashConfirm = '';
                    this.$dispatch('open-modal', 'kosongkan-sampah');
                },

                init() {
                    // Filter tanpa reload menukar isi #riwayatTable, jadi id yang
                    // tadinya dipilih sudah tidak ada. Membiarkan `selection`
                    // berisi id basi berarti toolbar masih tampil padahal tidak
                    // ada yang bisa dipulihkan.
                    window.addEventListener('filters-applied', () => {
                        this.selection = [];
                    });
                },
            };
        }
    </script>

</body>
</html>
