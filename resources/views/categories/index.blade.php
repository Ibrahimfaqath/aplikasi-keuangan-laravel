<!DOCTYPE html>
<html lang="id" class="h-full">

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

    @include('partials.theme-boot')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

{{-- Permukaan & teks halaman sekarang berasal dari token tema daisyUI
     (resources/css/app.css + tailwind.config.js), jadi <html>/<body> tidak
     perlu warna manual lagi. --}}
<body class="app-shell-content min-h-full font-sans antialiased text-base-content">

    <x-sidebar title="Kelola Kategori" :back="route('transactions.index')" minimal />

    <x-flash />


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


    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6 sm:py-8"
         x-data="categoryPage(@js($itemsPayload), @js($usage), @js($editable), @js(\App\Support\CategoryStyle::COLORS), @js(\App\Support\CategoryStyle::iconDSet()))"
         @keydown.escape.window="if (deleteId !== null) { closeDelete() } else { closeModal() }">

        @if ($isDemo)
        {{-- `alert` dipinjam untuk bentuk + radius-nya saja, tata letaknya tetap
             baris (utility `flex` menimpa grid bawaan daisyUI). Warnanya lewat
             token semantic — lihat alasannya di components/flash.blade.php:
             modifier alert-warning/warning dicampur base-100 sehingga kontrasnya
             pecah di mode gelap. --}}
        <div class="alert flex items-start gap-2.5 p-3.5 mb-5 border border-warning/30 bg-warning/10 text-base-content">
            <svg class="w-4 h-4 mt-0.5 shrink-0 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
            <p class="text-xs font-semibold text-warning">
                Mode demo — daftar kategori hanya bisa dilihat, bukan diubah.
                <a href="{{ route('register') }}" class="link">Daftar gratis</a> untuk mencoba menambah kategori.
            </p>
        </div>
        @endif

        {{-- Judul halaman + aksi utama --}}
        <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
            <div class="min-w-0">
                <h2 class="text-lg font-bold tracking-tight text-base-content">Kategori Transaksi</h2>
                <p class="text-xs text-base-content/60 mt-1 leading-relaxed max-w-md">
                    Kategori bawaan sudah siap dipakai. Tambah kategori sendiri untuk menyesuaikan catatanmu.
                </p>
            </div>
            @unless ($isDemo)
            <button type="button" @click="openCreate()" class="btn btn-primary btn-sm">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Tambah Kategori
            </button>
            @endunless
        </div>

        {{-- Ringkasan. `card` untuk permukaan, `stat-title`/`stat-value`/
             `stat-desc` untuk tipografi triplet (label / angka / catatan);
             ukuran tetap dikunci lewat utility supaya tile tetap padat. --}}
        <div class="grid grid-cols-3 gap-2.5 mb-5">
            <div class="card bg-base-100 border border-base-300 shadow-sm p-3.5">
                <p class="stat-title text-[10px] font-bold uppercase tracking-wider">Total</p>
                <p class="stat-value text-xl mt-1">{{ $stats['total'] }}</p>
                <p class="stat-desc text-[11px] mt-0.5 leading-snug">
                    {{ $stats['total'] - $stats['custom'] }} bawaan<br>{{ $stats['custom'] }} custom
                </p>
            </div>
            <div class="card bg-base-100 border border-base-300 shadow-sm p-3.5">
                <p class="stat-title text-[10px] font-bold uppercase tracking-wider">Terpakai</p>
                <p class="stat-value text-xl mt-1">{{ $stats['used'] }}</p>
                <p class="stat-desc text-[11px] mt-0.5">dari {{ $stats['total'] }} kategori</p>
            </div>
            <div class="card bg-base-100 border border-base-300 shadow-sm p-3.5">
                <p class="stat-title text-[10px] font-bold uppercase tracking-wider">Belum dipakai</p>
                <p class="stat-value text-xl mt-1">{{ $stats['unused'] }}</p>
                <p class="stat-desc text-[11px] mt-0.5">tanpa transaksi</p>
            </div>
        </div>

        {{-- Cari + saring jenis --}}
        <div class="card bg-base-100 border border-base-300 shadow-sm p-3 mb-5">
            <div class="flex flex-col sm:flex-row gap-2.5">
                <div class="relative flex-1 min-w-0">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-base-content/60 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <input type="text" x-model="search" placeholder="Cari kategori..." aria-label="Cari kategori"
                           class="input input-bordered h-10 w-full pl-10 pr-9 text-sm placeholder:text-base-content/60">
                    <button type="button" x-show="search !== ''" @click="search = ''" x-cloak
                            aria-label="Hapus pencarian"
                            class="btn btn-ghost btn-xs btn-circle absolute right-2 top-1/2 -translate-y-1/2 text-base-content/60 hover:text-base-content">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Segmented control -> `tabs tabs-boxed`. `tabs-boxed` sudah
                     menata himself (latar base-200, radius, padding 1) dan
                     memberi state aktif ke anaknya, jadi cabang Alpine hanya
                     perlu menyalakan `tab-active`. Kedua cabang tetap string
                     LITERAL — bukan dirangkai dari variabel, kalau tidak akan
                     ikut ter-purge. --}}
                <div class="tabs tabs-boxed bg-base-100 border border-base-300 grid-cols-3 h-10 w-full sm:w-auto sm:inline-grid" role="group" aria-label="Saring jenis kategori">
                    @foreach ([['key' => 'all', 'label' => 'Semua'], ['key' => 'income', 'label' => 'Masuk'], ['key' => 'expense', 'label' => 'Keluar']] as $filter)
                    <button type="button" @click="tab = '{{ $filter['key'] }}'" :aria-pressed="tab === '{{ $filter['key'] }}'"
                            class="tab h-full px-3 text-sm font-semibold whitespace-nowrap"
                            :class="tab === '{{ $filter['key'] }}'
                                ? '!bg-base-300 !text-base-content'
                                : ''">
                        {{ $filter['label'] }}
                        <span class="ms-1.5 opacity-60 tabular-nums" x-text="matchCount('{{ $filter['key'] }}')"></span>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Daftar kategori --}}
        @foreach ($groups as $type => $group)
        <section class="card bg-base-100 border border-base-300 shadow-sm overflow-hidden mb-5"
                 x-show="tab === 'all' || tab === '{{ $type }}'">
            <div class="flex items-center gap-3 px-4 sm:px-5 py-4 border-b border-base-300">
                <span class="w-9 h-9 shrink-0 rounded-box flex items-center justify-center {{ $type === 'income' ? 'bg-success/10 text-success' : 'bg-error/10 text-error' }}">
                    <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! $group['icon'] !!}</svg>
                </span>
                <div class="min-w-0">
                    <h3 class="text-sm font-bold text-base-content">{{ $group['title'] }}</h3>
                    <p class="text-[11px] text-base-content/60 mt-0.5 truncate">{{ $group['desc'] }}</p>
                </div>
                {{-- Tanpa pencarian cukup totalnya; saat searching tampil "cocok/total". --}}
                <span class="badge badge-ghost badge-sm ml-auto shrink-0 font-semibold"
                      x-text="search.trim() === '' ? countOf('{{ $type }}') : visibleCount('{{ $type }}') + '/' + countOf('{{ $type }}')"></span>
            </div>

            <ul class="p-4 sm:p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5"
                x-show="visibleCount('{{ $type }}') > 0">
                @foreach ($group['items'] as $item)
                <li x-show="matches('{{ $type }}', @js($item['name']))"
                    class="card group flex-row items-start gap-3 bg-base-100 border border-base-300 px-3.5 py-3 hover:border-base-content/30 hover:bg-base-300/40 transition-colors">
                    <span class="w-9 h-9 shrink-0 rounded-box flex items-center justify-center {{ \App\Support\CategoryStyle::colorClasses($item['color']) }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">{!! \App\Support\CategoryStyle::iconPath($item['icon']) !!}</svg>
                    </span>

                    {{-- Nama dapat lebar penuh baris pertamanya; badge pindah ke
                         baris meta. Sebelumnya badge berdiri di kanan baris yang
                         sama, dan begitu kartu jadi sempit (grid makin lebar)
                         nama terpotong & tumpang tindih dengan badge. --}}
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-base-content leading-snug line-clamp-2 break-words" title="{{ $item['name'] }}">{{ $item['name'] }}</p>

                        <div class="mt-1 flex items-center gap-x-2 gap-y-1 flex-wrap min-w-0">
                            @php($used = $usage[$item['name']] ?? 0)
                            @if ($used > 0)
                            {{-- Dulu teks mati. Sekarang link ke /transactions yang
                                 sudah mendukung filter kategori. --}}
                            <a href="{{ route('transactions.index', ['category' => $item['name']]) }}"
                               class="link inline-flex items-center gap-0.5 text-[11px] text-base-content/60 whitespace-nowrap"
                               title="Lihat {{ $used }} transaksi memakai {{ $item['name'] }}">
                                {{ $used }} transaksi
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                            @else
                            <span class="text-[11px] text-base-content/60 whitespace-nowrap">Belum dipakai</span>
                            @endif

                            @if ($item['is_global'])
                            <span class="badge badge-ghost badge-sm shrink-0 text-[10px] uppercase tracking-wider text-base-content/60"
                                  title="Kategori bawaan — selalu tersedia, tidak bisa diubah atau dihapus">Bawaan</span>
                            @else
                            <span class="badge badge-primary badge-sm shrink-0 text-[10px] uppercase tracking-wider"
                                  title="Kategori buatanmu — bisa diubah atau dihapus">Custom</span>
                            @endif
                        </div>
                    </div>

                    @unless ($isDemo)
                    {{-- Aksi ubah & hapus muncul saat hover/fokus supaya kartu
                         yang cuma dibaca tetap bersih.
                         absolutely-positioned dengan menghitung itself: kalau tetap di
                         flow, dua tombol 32px ini memakan ~68px yang tidak
                         pernah terlihat sampai hover — Persis ruang yang
                         membuat nama kategori terpotong di kartu sempit.
                         Ditumpuk absolute + backdrop netral supaya teks di
                         bawahnya tidak pernah kelihatan menembus. --}}
                    @unless ($item['is_global'])
                    <div class="relative shrink-0 flex items-center gap-0.5
                                sm:absolute sm:right-2 sm:top-2 sm:shrink
                                sm:opacity-0 sm:group-hover:opacity-100 sm:focus-within:opacity-100
                                transition-opacity">
                        {{-- Backdrop hanya di sm+: di mobile tombolnya memang
                             terlihat, jadi tidak ada yang perlu disamarkan. --}}
                        <span class="hidden sm:block absolute inset-0 -z-10 rounded-btn bg-base-100 group-hover:bg-base-300/40"></span>

                        <button type="button" @click='editCategory(@js($item['id']))'
                                class="btn btn-ghost btn-sm btn-square text-base-content/60 hover:text-base-content"
                                title="Ubah kategori {{ $item['name'] }}" aria-label="Ubah kategori {{ $item['name'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>

                        {{-- Sengaja BUKAN <form>. Kalau formnya ada, klik tetap
                             terkirim ke server begitu JS gagal dimuat — menghapus
                             kategori tanpa konfirmasi sama sekali. Tombol mati
                             lebih baik daripada diam-diam menghapus. Pengiriman
                             sungguhan dilakukan form tersembunyi #deleteForm. --}}
                        <button type="button" @click="askDelete(@js($item['id']), @js($item['name']), {{ $used }})"
                                class="btn btn-ghost btn-sm btn-square text-base-content/60 hover:bg-error/10 hover:text-error"
                                title="Hapus kategori {{ $item['name'] }}" aria-label="Hapus kategori {{ $item['name'] }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a2 2 0 00-1-1h-4a2 2 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                    @endunless
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
        <div class="card items-center bg-base-100 border border-base-300 shadow-sm px-4 sm:px-5 py-10 text-center mb-5"
             x-show="totalVisible() === 0" x-cloak>
            <span class="inline-flex items-center justify-center w-10 h-10 rounded-box border border-base-300 bg-base-100 text-base-content/60">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            </span>
            <p class="text-xs font-semibold text-base-content mt-3">Tidak ada kategori yang cocok</p>
            <p class="text-[11px] text-base-content/60 mt-1">Coba kata kunci lain, atau tampilkan semua kategori lagi.</p>
            <button type="button" @click="resetFilters()" class="btn btn-sm mt-3">
                Tampilkan semua
            </button>
        </div>

        <p class="text-[11px] text-base-content/60 text-center mt-6 px-4 max-w-xl mx-auto leading-relaxed">
            Kategori bawaan selalu tersedia dan tidak bisa dihapus. Menghapus kategori custom
            tidak menghapus riwayat — kategori lama tetap tersimpan pada tiap transaksi.
        </p>

        {{-- Modal tambah / ubah kategori.
             Satu form untuk dua keperluan: aksi "Tambah" buka dalam mode
             tambah (action = store), tombol ubah di tiap kartu membuka
             mode ubah (action = update ke kategori itu).

             Dibangun di atas `modal`/`modal-box` daisyUI. `.modal` sudah
             tersembunyi secara bawaan dan hanya tampil saat elementnya punya
             kelas `modal-open`, jadi openModal()/closeModal() di <script>
             me-toggle `modal-open` — bukan `hidden`. Id dan nama yang dipakai
             JS tetap sama persis. `p-0` karena isi bawah sudah membawa
             padding sendiri. --}}
        <div id="categoryModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <div class="modal-box p-0 sm:max-w-md">

                <div class="flex items-center justify-between p-6 border-b border-base-300">
                    <div class="flex items-center gap-3">
                        {{-- Pratinjau langsung warna + ikon yang dipilih, supaya
                            ikumu jadi terasa sebelum disimpan. --}}
                        <span class="p-2.5 rounded-box shrink-0" :class="appearanceClass()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path :d="iconD()"/></svg>
                        </span>
                        <div>
                            <h3 id="category-modal-title" class="text-base font-bold text-base-content" x-text="isEditing() ? 'Ubah Kategori' : 'Tambah Kategori'"></h3>
                            <p class="text-xs text-base-content/60" x-text="isEditing() ? 'Transaksi & anggaran lama ikut mengikuti perubahan ini.' : 'Langsung tersedia di form transaksi & asisten AI'"></p>
                        </div>
                    </div>
                    <button type="button" @click="closeModal()" aria-label="Tutup"
                            class="btn btn-ghost btn-sm btn-circle text-base-content/60">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="formAction()" method="POST" class="p-6 space-y-4">
                    @csrf
                    <template x-if="isEditing()"><input type="hidden" name="_method" value="PATCH"></template>
                    <input type="hidden" name="editing_id" :value="editingId">

                    <div>
                        <label for="category-name" class="block text-xs font-semibold text-base-content/70 mb-2">Nama Kategori</label>
                        <input type="text" name="name" id="category-name" x-model="name" value="{{ old('name') }}"
                               placeholder="Contoh: Jualan Online" autocomplete="off"
                               maxlength="50" required
                               class="input w-full text-sm placeholder:text-base-content/60"
                               :class="isDuplicate() || nameError
                                   ? 'input-error'
                                   : 'input-bordered'">
                        <div class="mt-1.5 min-h-[16px]">
                            @error('name')
                                <p class="text-[11px] font-semibold text-error">{{ $message }}</p>
                            @else
                                <p x-show="isDuplicate()" x-cloak class="text-[11px] font-semibold text-error">Kategori ini sudah dipakai pada jenis transaksi tersebut.</p>
                                <p x-show="! isDuplicate()" class="text-[11px] text-base-content/60">Maksimal 50 karakter · tersisa <span x-text="50 - name.length"></span></p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-base-content/70 mb-2">Jenis Transaksi</span>
                        {{-- Radio asli (bukan button) tetap dipakai supaya nilanya
                             ikut form; hanya tampilannya yang jadi `tab`. Warna aktif
                             dijaga `has-[:checked]:` karena input-nya sr-only. --}}
                        <div class="tabs tabs-boxed bg-base-100 border border-base-300 grid-cols-2 w-full h-12" role="radiogroup" aria-label="Jenis Transaksi">
                            @foreach (['income' => 'Pemasukan', 'expense' => 'Pengeluaran'] as $value => $label)
                            <label class="tab h-full cursor-pointer select-none text-base-content/60 hover:text-base-content has-[:checked]:bg-primary has-[:checked]:text-primary-content">
                                <input type="radio" name="type" value="{{ $value }}" x-model="type" class="sr-only" required>
                                <span class="text-xs sm:text-sm font-bold">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('type')
                            <p class="text-[11px] font-semibold text-error mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Warna & ikon --}}
                    <div>
                        <span class="block text-xs font-semibold text-base-content/70 mb-2">Warna</span>
                        <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Warna kategori">
                            <template x-for="key in colorKeys" :key="key">
                                <button type="button" @click="color = key" :aria-pressed="color === key" role="radio"
                                        :aria-label="'Warna ' + key"
                                        :title="key"
                                        class="btn btn-xs btn-square h-7 w-7 border-0"
                                        :class="[palette[key] || '', color === key ? 'ring-2 ring-base-content ring-offset-2 ring-offset-base-100' : '']">
                                    <svg class="w-3.5 h-3.5" x-show="color === key" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="color" :value="color">
                    </div>

                    <div>
                        <span class="block text-xs font-semibold text-base-content/70 mb-2">Ikon</span>
                        <div class="grid grid-cols-8 gap-1 max-h-32 overflow-y-auto p-1 -m-1" role="radiogroup" aria-label="Ikon kategori">
                            <template x-for="key in iconKeys" :key="key">
                                <button type="button" @click="icon = key" :aria-pressed="icon === key" role="radio"
                                        :aria-label="'Ikon ' + key" :title="key"
                                        class="btn btn-xs btn-square h-8 w-8"
                                        :class="icon === key
                                            ? 'btn-primary'
                                            : 'btn-ghost text-base-content/60'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path :d="iconSet[key]"/></svg>
                                </button>
                            </template>
                        </div>
                        <input type="hidden" name="icon" :value="icon">
                    </div>

                    {{-- Peringatan kalau ganti jenis akan ikut memindahkan
                         transaksi — ini keputusan yang cukup besar, jadi
                        harus terlihat, bukan diam-diam terjadi. --}}
                    <div x-show="retypeWarning()" x-cloak
                         class="alert flex items-start gap-2.5 p-3 border border-warning/30 bg-warning/10 text-base-content">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
                        <p class="text-[11px] font-semibold text-warning">
                            <span x-text="editingUsed > 0 ? editingUsed + ' transaksi ikut dipindahkan jenisnya.' : 'Jenis kategori akan berubah.'"></span>
                            Riwayat nominalnya tetap sama.
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-base-300">
                        <button type="button" @click="closeModal()" class="btn btn-outline btn-sm">Batal</button>
                        <button type="submit" :disabled="! canSubmit()"
                                class="btn btn-primary btn-sm disabled:opacity-40"
                                x-text="isEditing() ? 'Simpan Perubahan' : 'Simpan Kategori'"></button>
                    </div>
                </form>
            </div>

            {{-- Overlay + penutup klik-tubuh. `modal-backdrop` membentang penuh
                 di dalam grid `.modal`, jadi klik di luar kotak tetap kena
                 elemen ini (sama seperti components/modal.blade.php). --}}
            <div class="modal-backdrop bg-neutral-950/60 backdrop-blur-sm" @click="closeModal()"></div>
        </div>

        {{-- Dialog konfirmasi hapus. Mengganti confirm() bawaan browser yang
             tampilannya beda jauh dari sisa UI (dan tidak bisa di-style).
             Visibilitas lewat `modal-open`, sama seperti #categoryModal. --}}
        <div id="deleteModal" class="modal" role="alertdialog" aria-modal="true" aria-labelledby="delete-modal-title">
            <div class="modal-box p-0 sm:max-w-sm">
                <div class="p-6">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-box bg-error/10 text-error">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2.25 2.25 0 0116.138 21H7.862a2.25 2.25 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </span>
                    <h3 id="delete-modal-title" class="text-base font-bold text-base-content mt-4">Hapus kategori ini?</h3>
                    <p class="text-xs text-base-content/70 mt-2 leading-relaxed">
                        <span class="font-semibold text-base-content" x-text="deleteName"></span>
                        akan hilang dari pilihan di form transaksi.
                    </p>
                    <p class="text-[11px] text-base-content/60 mt-2 leading-relaxed">
                        <span x-show="deleteUsed > 0"
                              x-text="'Transaksi yang sudah tercatat (' + deleteUsed + ') tetap utuh — kategorinya tersimpan di riwayat, hanya pilihannya yang hilang.'"></span>
                        <span x-show="deleteUsed === 0">Kategori ini belum dipakai transaksi apa pun, jadi tidak ada riwayat yang tersentuh.</span>
                    </p>
                </div>
                <div class="flex items-center justify-end gap-3 px-6 py-4 bg-base-100 border-t border-base-300">
                    <button type="button" @click="closeDelete()" class="btn btn-outline btn-sm">Batal</button>
                    <button type="button" @click="confirmDelete()" class="btn btn-error btn-sm">Ya, hapus</button>
                </div>
            </div>

            <div class="modal-backdrop bg-neutral-950/60 backdrop-blur-sm" @click="closeDelete()"></div>
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
                // Hasil $errors->has('name') harus jadi boolean JS, bukan teks
                // PHP: Blade tidak mengevaluasi PHP di dalam atribut HTML biasa,
                // jadi `$errors->has(...)` akan masuk mentah ke parser Alpine.
                nameError: @js($errors->has('name')),
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

                // daisyUI `.modal` tersembunyi secara bawaan (pointer-events:
                // none, opacity 0) dan baru tampil saat elementnya dapat kelas
                // `modal-open`. Karena itu buka/tutup di sini TIDAK memakai
                // `hidden` — hanya `modal-open`. Id tetap sama, jadi tidak ada
                // selector yang perlu berubah.
                openModal() {
                    const modal = document.getElementById('categoryModal');
                    if (!modal) return;
                    modal.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                    const input = document.getElementById('category-name');
                    if (input) window.setTimeout(() => { input.focus(); input.select(); }, 50);
                },

                closeModal() {
                    const modal = document.getElementById('categoryModal');
                    if (!modal) return;
                    modal.classList.remove('modal-open');
                    document.body.style.overflow = '';
                },

                appearanceClass() {
                    return this.palette[this.color] || this.palette.neutral || '';
                },

                iconD() {
                    return this.iconSet[this.icon] || this.iconSet.tag || '';
                },

                // Peringatan "ikut berubah jenisnya" hanya relevan saat
                // kategori yang dipakai transaksi benar-benar dipindah jenisnya.
                retypeWarning() {
                    return this.isEditing() && this.type !== this.editingOriginalType;
                },

                // Id kategori milik user untuk sebuah nama + jenis, atau null
                // kalau nama itu bukan kategori custom (mis. kategori bawaan).
                ownIdFor(name, type) {
                    const row = this.editable.find((r) => r.name === name && r.type === type);
                    return row ? row.id : null;
                },

                // Mode edit: kategori yang sedang diedit bukan bentrok dengan
                // dirinya sendiri. Tanpa pengecualian ini, `canSubmit()` selalu
                // false begitu modal dibuka — tombol Simpan mati dan tidak ada
                // satupun edit yang bisa disimpan kecuali kalau namanya diganti.
                isDuplicate() {
                    const name = this.name.trim().toLowerCase();
                    if (name === '') return false;

                    return (this.items[this.type] || []).some((item) => {
                        if (String(item).trim().toLowerCase() !== name) return false;
                        if (!this.isEditing()) return true;
                        // Number() di kedua sisi: editingId datang sebagai
                        // number dari editCategory(), tapi sebagai string
                        // setelah halaman dimuat ulang karena validasi gagal
                        // (nilai old() selalu string). Tanpa ini, !== selalu
                        // true dan tombol Simpan mati lagi.
                        return Number(this.ownIdFor(item, this.type)) !== Number(this.editingId);
                    });
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
                    modal.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                },

                closeDelete() {
                    const modal = document.getElementById('deleteModal');
                    if (!modal) return;
                    modal.classList.remove('modal-open');
                    this.deleteId = null;
                    // Scroll body baru dilepas kalau #categoryModal juga sudah
                    // tutup — dua modal ini bisa bertumpuk.
                    if (! document.getElementById('categoryModal').classList.contains('modal-open')) {
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
