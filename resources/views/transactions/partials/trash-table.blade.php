{{-- Isi tabel Sampah (desktop + mobile + pagination + empty state).
     Dipakai dari halaman penuh lewat @include, dan tetap jadi satu sumber
     markup supaya desktop & mobile tidak pernah diverge.

     Permukaan & teks sekarang diambil dari token tema daisyUI
     (bg-base-100/200/300, text-base-content, border-base-300) plus komponennya
     (table, checkbox, btn, badge) — lihat resources/css/app.css.

     Aturan yang tidak boleh dilanggar di file ini:

       * CLASS NAMA WAJIB LITERAL. Tailwind memindai sumber sebagai teks, jadi
         kelas yang dirangkai dari variabel (`text-{{ $x }}`) tidak pernah
         terlihat dan ikut ter-purge. Semua warna di bawah ditulis utuh.

       * SELURUH id / data-* / binding Alpine DIJAGA PERSIS. File ini dirender
         dua kali — sebagai halaman penuh dan sebagai payload `?partial=1`
         (`tableHtml`) — dan elemen-elemen itu dibaca mesin filter di
         resources/js/app.js (`#riwayatTable`, `tr[data-trash-item]`,
         `data-trash-*`) serta Alpine `trashPage()`.

     Butuh:
       $transactions      LengthAwarePaginator (atau iterable)
       $hasActiveFilters  bool — membedakan "Sampah kosong" dari
                          "filter tidak cocok", dua kondisi yang beda artinya
                          tapi tadinya menampilkan pesan yang sama. --}}

@php
    $paginator = $transactions ?? null;
    $items = $paginator && method_exists($paginator, 'items')
        ? $paginator->items()
        : collect($paginator ?? []);

    $isDemo = \App\Services\DemoMode::isEnabled() && \App\Services\DemoMode::isDemoUser(Auth::user());

    // Pengelompokan waktu: di Sampah yang dicari biasanya "yang baru saja
    // saya hapus". "3 minggu yang lalu" jauh lebih informatif daripada
    // tanggal polos, jadi pakai label relatif untuk yang dekat dan nama
    // bulan untuk yang sudah lama.
    //
    // Dua hal yang mudah salah di sini:
    // 1. Carbon 3 membalikkan diffInDays() sebagai float, jadi harus dibulatkan
    //    -- tanpa itu labelnya jadi "2.7504764626505 hari lalu".
    // 2. Selisihnya dihitung antar HARI (startOfDay), bukan antar 24 jam.
    //    Kalau dihitung per jam, transaksi yang dihapus semalam jam 8 pagi
    //    masih terhitung "Hari ini" -- padahal user baca "Kemarin" sebagai
    //    seluruh hari kemarin.
    $monthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    $groupLabel = function ($deletedAt) use ($monthNames) {
        $deleted = \Carbon\Carbon::parse($deletedAt);
        $days = (int) $deleted->copy()->startOfDay()->diffInDays(\Carbon\Carbon::now()->startOfDay(), false);

        if ($days < 1) {
            return 'Hari ini';
        }
        if ($days < 2) {
            return 'Kemarin';
        }
        if ($days < 30) {
            return $days.' hari lalu';
        }

        return $monthNames[(int) $deleted->format('n')].' '.$deleted->format('Y');
    };

    $groups = [];
    foreach ($items as $item) {
        $groups[$groupLabel($item->deleted_at)][] = $item;
    }

    // Warna kategori tetap milik domain data (lihat CategoryStyle), jadi di
    // sini hanya bentuknya yang dipinjam dari `badge`: padding, jarak ikon,
    // dan tinggi seragam. Warnanya tetap literal per warna.
    $pillThemes = [
        'green'   => 'border-green-500/30 bg-green-500/10 text-green-600 dark:text-green-400',
        'violet'  => 'border-violet-500/30 bg-violet-500/10 text-violet-600 dark:text-violet-400',
        'orange'  => 'border-orange-500/30 bg-orange-500/10 text-orange-600 dark:text-orange-400',
        'blue'    => 'border-blue-500/30 bg-blue-500/10 text-blue-600 dark:text-blue-400',
        'amber'   => 'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400',
        'neutral' => 'border-base-300 bg-base-100 text-base-content/70',
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

    $forceUrl = fn ($item) => route('transactions.force-destroy', $item->id);

    // Satu tempat untuk checkbox, supaya bentuk desktop dan mobile tidak
    // lama-lama berbeda. Label diambil lewat aria-label karena checkbox-nya
    // berdiri sendiri tanpa teks yang menjelaskan apa yang sedang dipilih.
    $checkBox = function ($item) {
        return '<input type="checkbox" name="ids[]" value="'.$item->id.'" x-model="selection"'
            .' aria-label="Pilih '.e($item->title).'"'
            .' class="checkbox checkbox-sm">';
    };
@endphp

{{-- ============================ DESKTOP ============================ --}}
{{-- `table` untuk tipografi & border baris, `table-zebra` untuk pemisahan
     baris ganjil/even tanpa utility divide per sel.

     CATATAN: `!bg-...` dipakai pada baris grup, hover, dan baris terpilih.
     `.table-zebra` menyasar `tbody tr:nth-child(odd)` — spesifisitasnya lebih
     tinggi dari utility biasa, jadi `bg-*` tanpa `!` kalah dan tidak terlihat. --}}
<div class="hidden md:block overflow-x-auto">
    <table class="table table-zebra">
        <thead>
            <tr class="!bg-base-100 border-b border-base-300 text-base-content/60 text-xs font-semibold uppercase tracking-wider">
                <th class="py-3.5 pl-4 pr-0 w-10">
                    <input type="checkbox" x-model="allSelected" :indeterminate="someSelected"
                           aria-label="Pilih semua transaksi di halaman ini"
                           class="checkbox checkbox-sm">
                </th>
                <th class="py-3.5 px-4">Tanggal</th>
                <th class="py-3.5 px-4">Bukti</th>
                <th class="py-3.5 px-4">Keterangan</th>
                <th class="py-3.5 px-4">Kategori</th>
                <th class="py-3.5 px-4 text-right">Nominal</th>
                <th class="py-3.5 px-4">Dihapus</th>
                <th class="py-3.5 px-4 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="text-xs sm:text-sm">
            @forelse ($groups as $groupName => $groupItems)
                <tr class="!bg-base-100 border-y border-base-300">
                    <td colspan="8" class="py-2 px-4 text-[11px] font-bold uppercase tracking-wider text-base-content/60">
                        {{ $groupName }}
                    </td>
                </tr>
                @foreach ($groupItems as $item)
                    @php
                        $deletedAt = \Carbon\Carbon::parse($item->deleted_at);
                        $amountText = \App\Services\AmountFormatter::compact($item->amount);
                        $signedAmount = ($item->type === 'income' ? '+' : '−').' '.$amountText;
                    @endphp
                    <tr class="transition hover:!bg-base-300/40"
                        data-trash-item
                        data-trash-title="{{ $item->title }}"
                        data-trash-amount="{{ $signedAmount }}"
                        :class="isSelected('{{ $item->id }}') && '!bg-base-300'">
                        <td class="py-4 pl-4 pr-0">{!! $checkBox($item) !!}</td>
                        <td class="py-4 px-4 font-medium text-base-content/60 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                        </td>
                        <td class="py-4 px-4">
                            <div class="relative w-8 h-8">
                                @if(! empty($item->image))
                                    <a href="{{ asset('storage/' . $item->image) }}" target="_blank" rel="noopener">
                                        <img src="{{ asset('storage/' . $item->image) }}"
                                             loading="lazy"
                                             class="w-8 h-8 rounded-box object-cover border border-base-300"
                                             alt="Bukti {{ $item->title }}">
                                    </a>
                                @else
                                    <div class="flex h-8 w-8 items-center justify-center rounded-box border border-dashed border-base-300 text-base-content/60" title="Tidak ada bukti">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="py-4 px-4 font-semibold text-base-content">
                            <span class="block max-w-[150px] truncate lg:max-w-[200px] xl:max-w-[260px]" title="{{ $item->title }}">{{ $item->title }}</span>
                        </td>
                        <td class="py-4 px-4 whitespace-nowrap">
                            <span class="badge badge-sm h-auto rounded-full px-2.5 py-1 max-w-[140px] gap-1.5 font-semibold border xl:max-w-[180px] {{ $pillFor($item->category) }}" title="{{ $item->category }}">
                                <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
                                <span class="truncate">{{ $item->category }}</span>
                            </span>
                        </td>
                        <td class="py-4 px-4 text-right font-extrabold whitespace-nowrap privacy-target {{ $item->type === 'income' ? 'text-success' : 'text-error' }}"
                            data-amount="{{ $signedAmount }}">{{ $signedAmount }}</td>
                        <td class="py-4 px-4 font-medium text-base-content/60 whitespace-nowrap"
                            title="{{ $deletedAt->format('d M Y, H:i') }}">
                            <time datetime="{{ $deletedAt->toIso8601String() }}">{{ $deletedAt->diffForHumans() }}</time>
                        </td>
                        <td class="py-4 px-4">
                            <div class="inline-flex items-center gap-1 justify-end">
                                {{-- Aksi aman (Pulihkan) dibuat dominan, aksi destruktif
                                     sengaja redup. Dulu keduanya sama besar dan
                                     sama kraft, padahal hanya satu yang bisa
                                     dibatalkan -- dan itu yang paling sering
                                     diklik tanpa sengaja.

                                     `btn-xs` menurunkan tinggi ke 1.5rem; tanpa
                                     itu `.btn` memaksa min-height 3rem dan baris
                                     tabel jadi tidak seragam. --}}
                                @if ($isDemo)
                                    <span class="btn btn-ghost btn-xs btn-disabled" title="Mode demo terkunci">Pulihkan</span>
                                @else
                                    <form action="{{ route('transactions.restore', $item->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                aria-label="Pulihkan transaksi {{ $item->title }}"
                                                class="btn btn-primary btn-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            Pulihkan
                                        </button>
                                    </form>
                                @endif

                                @if ($isDemo)
                                    <span class="btn btn-ghost btn-xs cursor-not-allowed text-base-content/60" title="Mode demo terkunci">Hapus</span>
                                @else
                                    <button type="button"
                                            @click="askForceDestroy($el)"
                                            data-url="{{ $forceUrl($item) }}"
                                            data-title="{{ $item->title }}"
                                            data-amount="{{ $signedAmount }}"
                                            aria-label="Hapus permanen transaksi {{ $item->title }}"
                                            title="Hapus permanen"
                                            class="btn btn-ghost btn-xs text-base-content/60 hover:bg-error/10 hover:text-error">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        Hapus
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="8" class="py-16 px-4 text-center">
                        @include('transactions.partials.trash-empty', [
                            'hasActiveFilters' => $hasActiveFilters ?? false,
                            'compact' => true,
                        ])
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ============================ MOBILE ============================ --}}
{{-- Kartu MOBILE sengaja TIDAK memakai kelas `table`/`table-zebra`: komponen
     itu menyetel display + border baris yang tidak berlaku di daftar kartu,
     dan akan merusak pembagian grup di atas. Permukaannya tetap dari token
     yang sama, jadi warnanya konsisten dengan tabel desktop.

     Badge kategori di sini memakai kelas yang sama persis dengan versi
     desktop (hasil $pillFor) — hanya bentuknya yang disederhanakan ke
     `badge badge-xs`, karena di layar sempit tinggi pill penuh bikin baris
     kedua meluber. --}}
<div class="block md:hidden divide-y divide-base-300">
    @forelse ($groups as $groupName => $groupItems)
        <div class="border-y border-base-300 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-base-content/60">
            {{ $groupName }}
        </div>
        @foreach ($groupItems as $item)
            @php
                $deletedAt = \Carbon\Carbon::parse($item->deleted_at);
                $amountText = \App\Services\AmountFormatter::compact($item->amount);
                $signedAmount = ($item->type === 'income' ? '+' : '−').' '.$amountText;
            @endphp
            {{-- Di layar sempit tidak muat checkbox di dalam kartu yang sama
                 dengan tombol aksi, jadi seluruh kartu jadi target ketuk
                 (role=button + aria-pressed) dan centangnya ditunjukkan lewat
                 cincin.

                 Kartu TIDAK punya checkbox sendiri: yang di-<div> tabel desktop
                 tetap ada di DOM dan tetap terkirim walau disembunyikan CSS
                 (`display:none` bukan `disabled`), dan Alpine yang menjaga
                 keduanya sinkron. Jadi setiap id terkirim satu kali.

                BINDING TIDAK BOLEH DIUBAH: `isSelected(...) && '...'` di
                 :class inilah yang membuat cincin selection sinkron dengan
                 checkbox tabel. Nama kelas di dalamnya tetap literal. --}}
            <div class="p-4 transition"
                 role="button" tabindex="0"
                 aria-pressed="false"
                 @click="toggleSelection('{{ $item->id }}')"
                 @keydown.enter.prevent="toggleSelection('{{ $item->id }}')"
                 @keydown.space.prevent="toggleSelection('{{ $item->id }}')"
                 :aria-pressed="isSelected('{{ $item->id }}') ? 'true' : 'false'"
                 :class="isSelected('{{ $item->id }}') && 'bg-base-100 ring-2 ring-base-content rounded-box'">
                <div class="flex items-center gap-3">
                    @if(! empty($item->image))
                        <a href="{{ asset('storage/' . $item->image) }}" target="_blank" rel="noopener" class="flex-shrink-0" @click.stop>
                            <img src="{{ asset('storage/' . $item->image) }}" loading="lazy" class="w-12 h-12 rounded-box object-cover border border-base-300" alt="Bukti {{ $item->title }}">
                        </a>
                    @else
                        <div class="flex-shrink-0 w-12 h-12 rounded-box border border-dashed border-base-300 text-base-content/60 flex items-center justify-center" title="Tidak ada bukti">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-base-content truncate text-sm sm:text-base">{{ $item->title }}</p>
                        <p class="text-[11px] font-medium text-base-content/60 mt-0.5">
                            {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                            · <span class="badge badge-xs rounded-full border {{ $pillFor($item->category) }}">{{ $item->category }}</span>
                        </p>
                    </div>
                    <p class="flex-shrink-0 font-extrabold text-sm sm:text-base whitespace-nowrap privacy-target {{ $item->type === 'income' ? 'text-success' : 'text-error' }}"
                       data-amount="{{ $signedAmount }}">{{ $signedAmount }}</p>
                </div>

                <div class="flex items-center justify-between gap-2 pt-3 mt-3 border-t border-base-300">
                    <time datetime="{{ $deletedAt->toIso8601String() }}"
                          class="text-[11px] font-medium text-base-content/60"
                          title="{{ $deletedAt->format('d M Y, H:i') }}">
                        Dihapus {{ $deletedAt->diffForHumans() }}
                    </time>

                    {{-- @click.stop: tombol di dalam kartu tidak boleh ikut
                         membalik pilihan, itu akan terasa seperti bug. --}}
                    <div class="inline-flex items-center gap-2" @click.stop>
                        @if ($isDemo)
                            <span class="btn btn-ghost btn-xs btn-disabled" title="Mode demo terkunci">Pulihkan</span>
                            <span class="btn btn-ghost btn-xs cursor-not-allowed text-base-content/60" title="Mode demo terkunci">Hapus</span>
                        @else
                            <form action="{{ route('transactions.restore', $item->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" aria-label="Pulihkan transaksi {{ $item->title }}"
                                        class="btn btn-primary btn-xs">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                    Pulihkan
                                </button>
                            </form>
                            <button type="button"
                                    @click="askForceDestroy($el)"
                                    data-url="{{ $forceUrl($item) }}"
                                    data-title="{{ $item->title }}"
                                    data-amount="{{ $signedAmount }}"
                                    aria-label="Hapus permanen transaksi {{ $item->title }}"
                                    class="btn btn-ghost btn-xs text-base-content/60 hover:bg-error/10 hover:text-error">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                Hapus
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @empty
        <div class="py-16 px-4">
            @include('transactions.partials.trash-empty', [
                'hasActiveFilters' => $hasActiveFilters ?? false,
            ])
        </div>
    @endforelse
</div>

{{-- Pembungkus pagination TIDAK memakai `pagination`: komponen itu tidak ada
     di daisyUI v4 (lihat daftar kelas terverifikasi), dan tautan di dalamnya
     harus tetap berupa <a> asli supaya mesin filter di app.js bisa menangkap
     klik lewat delegasi di #riwayat. Yang dipinjam hanya permukaan footer:
     bg-base-100 + border-base-300. --}}
@if ($paginator && method_exists($paginator, 'links') && $paginator->hasPages())
    <div class="px-6 py-3 border-t border-base-300">
        {{ $paginator->links('vendor.pagination.tailwind') }}
    </div>
@endif