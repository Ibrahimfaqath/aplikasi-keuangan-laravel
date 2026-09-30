{{-- Isi tabel Sampah (desktop + mobile + pagination + empty state).
     Dipakai dari halaman penuh lewat @include, dan tetap jadi satu sumber
     markup supaya desktop & mobile tidak pernah diverge.

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

    $forceUrl = fn ($item) => route('transactions.force-destroy', $item->id);

    // Satu tempat untuk checkbox, supaya bentuk desktop dan mobile tidak
    // lama-lama berbeda. Label diambil lewat aria-label karena checkbox-nya
    // berdiri sendiri tanpa teks yang menjelaskan apa yang sedang dipilih.
    $checkBox = function ($item) {
        return '<input type="checkbox" name="ids[]" value="'.$item->id.'" x-model="selection"'
            .' aria-label="Pilih '.e($item->title).'"'
            .' class="w-4 h-4 rounded border-neutral-300 dark:border-[#555555] text-neutral-900 dark:text-neutral-100 focus:ring-neutral-900 dark:focus:ring-neutral-100 cursor-pointer">';
    };
@endphp

{{-- ============================ DESKTOP ============================ --}}
<div class="hidden md:block overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-neutral-50 dark:bg-[#262626]/40 border-b border-neutral-200 dark:border-[#333333] text-neutral-500 dark:text-neutral-400 text-xs font-semibold uppercase tracking-wider">
                <th class="py-3.5 pl-4 pr-0 w-10">
                    <input type="checkbox" x-model="allSelected" :indeterminate="someSelected"
                           aria-label="Pilih semua transaksi di halaman ini"
                           class="w-4 h-4 rounded border-neutral-300 dark:border-[#555555] text-neutral-900 dark:text-neutral-100 focus:ring-neutral-900 dark:focus:ring-neutral-100 cursor-pointer">
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
        <tbody class="divide-y divide-neutral-100 dark:divide-[#262626] text-xs sm:text-sm">
            @forelse ($groups as $groupName => $groupItems)
                <tr class="bg-neutral-50 dark:bg-[#262626]/30">
                    <td colspan="8" class="py-2 px-4 text-[11px] font-bold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        {{ $groupName }}
                    </td>
                </tr>
                @foreach ($groupItems as $item)
                    @php
                        $deletedAt = \Carbon\Carbon::parse($item->deleted_at);
                        $amountText = \App\Services\AmountFormatter::compact($item->amount);
                        $signedAmount = ($item->type === 'income' ? '+' : '−').' '.$amountText;
                    @endphp
                    <tr class="transition hover:bg-neutral-50 dark:hover:bg-[#262626]/50"
                        data-trash-item
                        data-trash-title="{{ $item->title }}"
                        data-trash-amount="{{ $signedAmount }}"
                        :class="isSelected('{{ $item->id }}') && 'bg-neutral-100 dark:bg-[#262626]/60'">
                        <td class="py-4 pl-4 pr-0">{!! $checkBox($item) !!}</td>
                        <td class="py-4 px-4 font-medium text-neutral-500 dark:text-neutral-400 whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                        </td>
                        <td class="py-4 px-4">
                            <div class="relative w-8 h-8">
                                @if(! empty($item->image))
                                    <a href="{{ asset('storage/' . $item->image) }}" target="_blank" rel="noopener">
                                        <img src="{{ asset('storage/' . $item->image) }}"
                                             loading="lazy"
                                             class="w-8 h-8 rounded-lg object-cover border border-neutral-200 dark:border-[#333333]"
                                             alt="Bukti {{ $item->title }}">
                                    </a>
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
                        <td class="py-4 px-4 text-right font-extrabold whitespace-nowrap privacy-target {{ $item->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                            data-amount="{{ $signedAmount }}">{{ $signedAmount }}</td>
                        <td class="py-4 px-4 font-medium text-neutral-400 dark:text-neutral-500 whitespace-nowrap"
                            title="{{ $deletedAt->format('d M Y, H:i') }}">
                            <time datetime="{{ $deletedAt->toIso8601String() }}">{{ $deletedAt->diffForHumans() }}</time>
                        </td>
                        <td class="py-4 px-4">
                            <div class="inline-flex items-center gap-1 justify-end">
                                {{-- Aksi aman (Pulihkan) dibuat dominan, aksi destruktif
                                     sengaja redup. Dulu keduanya sama besar dan
                                     sama kraft, padahal hanya satu yang bisa
                                     dibatalkan -- dan itu yang paling sering
                                     diklik tanpa sengaja. --}}
                                @if ($isDemo)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 rounded-lg text-xs font-semibold cursor-not-allowed" title="Mode demo terkunci">Pulihkan</span>
                                @else
                                    <form action="{{ route('transactions.restore', $item->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                aria-label="Pulihkan transaksi {{ $item->title }}"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-lg text-xs font-semibold transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                                            Pulihkan
                                        </button>
                                    </form>
                                @endif

                                @if ($isDemo)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1.5 text-neutral-400 dark:text-neutral-500 rounded-lg text-xs font-semibold cursor-not-allowed" title="Mode demo terkunci">Hapus</span>
                                @else
                                    <button type="button"
                                            @click="askForceDestroy($el)"
                                            data-url="{{ $forceUrl($item) }}"
                                            data-title="{{ $item->title }}"
                                            data-amount="{{ $signedAmount }}"
                                            aria-label="Hapus permanen transaksi {{ $item->title }}"
                                            title="Hapus permanen"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-neutral-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-500/10 rounded-lg text-xs font-semibold transition-colors">
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
<div class="block md:hidden divide-y divide-neutral-100 dark:divide-[#262626]">
    @forelse ($groups as $groupName => $groupItems)
        <div class="bg-neutral-50 dark:bg-[#262626]/30 px-4 py-2 text-[11px] font-bold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
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
                 keduanya sinkron. Jadi setiap id terkirim satu kali. --}}
            <div class="p-4 transition"
                 role="button" tabindex="0"
                 aria-pressed="false"
                 @click="toggleSelection('{{ $item->id }}')"
                 @keydown.enter.prevent="toggleSelection('{{ $item->id }}')"
                 @keydown.space.prevent="toggleSelection('{{ $item->id }}')"
                 :aria-pressed="isSelected('{{ $item->id }}') ? 'true' : 'false'"
                 :class="isSelected('{{ $item->id }}') && 'bg-neutral-100 dark:bg-[#262626]/60 ring-2 ring-neutral-900 dark:ring-neutral-100 rounded-xl'">
                <div class="flex items-center gap-3">
                    @if(! empty($item->image))
                        <a href="{{ asset('storage/' . $item->image) }}" target="_blank" rel="noopener" class="flex-shrink-0" @click.stop>
                            <img src="{{ asset('storage/' . $item->image) }}" loading="lazy" class="w-12 h-12 rounded-xl object-cover border border-neutral-200 dark:border-[#333333]" alt="Bukti {{ $item->title }}">
                        </a>
                    @else
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl border border-dashed border-neutral-200 dark:border-[#333333] text-neutral-300 dark:text-neutral-600 flex items-center justify-center" title="Tidak ada bukti">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                        </div>
                    @endif
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-neutral-900 dark:text-neutral-50 truncate text-sm sm:text-base">{{ $item->title }}</p>
                        <p class="text-[11px] font-medium text-neutral-500 dark:text-neutral-400 mt-0.5">
                            {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                            · <span class="{{ $pillFor($item->category) }} rounded-full border px-1.5 py-px">{{ $item->category }}</span>
                        </p>
                    </div>
                    <p class="flex-shrink-0 font-extrabold text-sm sm:text-base whitespace-nowrap privacy-target {{ $item->type === 'income' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}"
                       data-amount="{{ $signedAmount }}">{{ $signedAmount }}</p>
                </div>

                <div class="flex items-center justify-between gap-2 pt-3 mt-3 border-t border-neutral-100 dark:border-[#262626]">
                    <time datetime="{{ $deletedAt->toIso8601String() }}"
                          class="text-[11px] font-medium text-neutral-400 dark:text-neutral-500"
                          title="{{ $deletedAt->format('d M Y, H:i') }}">
                        Dihapus {{ $deletedAt->diffForHumans() }}
                    </time>

                    {{-- @click.stop: tombol di dalam kartu tidak boleh ikut
                         membalik pilihan, itu akan terasa seperti bug. --}}
                    <div class="inline-flex items-center gap-2" @click.stop>
                        @if ($isDemo)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-100 dark:bg-[#262626] text-neutral-400 dark:text-neutral-500 rounded-lg text-xs font-semibold cursor-not-allowed" title="Mode demo terkunci">Pulihkan</span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1.5 text-neutral-400 dark:text-neutral-500 rounded-lg text-xs font-semibold cursor-not-allowed" title="Mode demo terkunci">Hapus</span>
                        @else
                            <form action="{{ route('transactions.restore', $item->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" aria-label="Pulihkan transaksi {{ $item->title }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-neutral-900 hover:bg-black dark:bg-neutral-100 dark:hover:bg-white dark:text-neutral-900 text-white rounded-lg text-xs font-semibold transition">
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
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-neutral-400 hover:text-red-600 hover:bg-red-50 dark:hover:text-red-400 dark:hover:bg-red-500/10 rounded-lg text-xs font-semibold transition-colors">
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

@if ($paginator && method_exists($paginator, 'links') && $paginator->hasPages())
    <div class="px-6 py-3 border-t border-neutral-200 dark:border-[#333333] bg-neutral-50 dark:bg-[#0A0A0A]">
        {{ $paginator->links('vendor.pagination.tailwind') }}
    </div>
@endif
