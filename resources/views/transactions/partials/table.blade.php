{{-- Isi tabel Riwayat (desktop + mobile + pagination).
     Dipakai dua kali: di halaman penuh lewat @include, dan di-render ulang
     oleh endpoint ?partial=1 saat filter berubah tanpa reload. Satu sumber
     markup, jadi tidak mungkin diverge. Butuh: $transactions --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="py-3.5 px-4">Tanggal</th>
                            <th class="py-3.5 px-4">Bukti</th>
                            <th class="py-3.5 px-4">Keterangan</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4 text-right">Nominal</th>
                            <th class="py-3.5 px-4 text-center no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300 text-xs sm:text-sm">
                        @php
                            $items = $transactions ?? $transaksi ?? [];
                            // Soft semantic icon pills — restrained: income green, investasi violet,
                            // food orange, transport blue, bills amber, shopping violet, lainnya neutral
                            $pillThemes = [
                                'green'   => 'bg-green-50 text-green-600 border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20',
                                'violet'  => 'bg-violet-50 text-violet-600 border-violet-200 dark:bg-violet-500/10 dark:text-violet-400 dark:border-violet-500/20',
                                'orange'  => 'bg-orange-50 text-orange-600 border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20',
                                'blue'    => 'bg-blue-50 text-blue-600 border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20',
                                'amber'   => 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                                'neutral' => 'bg-base-300 text-base-content/70 border-base-300',
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
                            $catVisuals = [
                                'Gaji'               => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>'],
                                'Bonus'              => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>'],
                                'Bisnis'             => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'],
                                'Investasi'          => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>'],
                                'Hadiah'             => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>'],
                                'Makanan & Minuman'  => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 2v20"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>'],
                                'Transportasi'       => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/>'],
                                'Tagihan & Utilitas' => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
                                'Belanja'            => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>'],
                                'Hiburan'            => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/>'],
                                'Kesehatan'          => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>'],
                                'Pendidikan'         => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>'],
                                'Keluarga'           => ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>'],
                            ];
                            $defaultCat = ['path' => '<path stroke-linecap="round" stroke-linejoin="round" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/>'];
                        @endphp
                        @forelse ($items as $item)
                        <tr class="hover:bg-base-300/50 transition">
                            <td class="py-4 px-4 font-medium text-base-content/60 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                            </td>
                            <td class="py-4 px-4">
                                <div class="relative w-8 h-8">
                                    @if(!empty($item->image))
                                    <a href="{{ asset('storage/' . $item->image) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $item->image) }}"
                                             loading="lazy"
                                             class="w-8 h-8 rounded-box object-cover border border-base-300"
                                             alt="Bukti">
                                    </a>
                                    @else
                                    {{-- Placeholder netral: kolom Bukti hanya bicara soal struk,
                                         identitas kategori sudah diwakili pill Kategori. --}}
                                    <div class="flex h-8 w-8 items-center justify-center rounded-box border border-dashed border-base-300 text-base-content/60"
                                         title="Tidak ada bukti">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/></svg>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-4 px-4 font-semibold text-base-content">
                                <span class="block max-w-[150px] truncate lg:max-w-[170px] xl:max-w-[240px]" title="{{ $item->title ?? $item->nama ?? $item->kategori }}">
                                    {{ $item->title ?? $item->nama ?? $item->kategori }}
                                </span>
                            </td>
                            <td class="py-4 px-4 whitespace-nowrap">
                                @php
                                    $catName = $item->category ?? 'Lainnya';
                                    $catIcon = $catVisuals[$catName] ?? $defaultCat;
                                @endphp
                                <span class="inline-flex max-w-[140px] items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold xl:max-w-[180px] {{ $pillFor($catName) }}" title="{{ $catName }}">
                                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $catIcon['path'] !!}</svg>
                                    <span class="truncate">{{ $catName }}</span>
                                </span>
                            </td>
                            <td class="py-4 px-4 text-right font-bold whitespace-nowrap privacy-target {{ ($item->type ?? 'income') == 'income' ? 'text-success' : 'text-error' }}"
data-amount="{{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} {{ \App\Services\AmountFormatter::compact($item->amount ?? $item->nominal ?? 0) }}">
                                {{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} {{ \App\Services\AmountFormatter::compact($item->amount ?? $item->nominal ?? 0) }}
                            </td>
                            <td class="py-4 px-4 text-center no-print">
                                <div class="inline-flex items-center gap-1">
                                    <a href="{{ route('transactions.edit', $item->id) }}" class="p-1.5 text-base-content/60 hover:text-base-content dark:hover:text-white hover:bg-base-300 rounded-box transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('transactions.destroy', $item->id) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" onclick="return confirm('Hapus transaksi ini?')" class="p-1.5 text-base-content/60 hover:text-base-content dark:hover:text-white hover:bg-base-300 rounded-box transition" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-base-100 border border-base-300 text-base-content/60 rounded-box flex items-center justify-center">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <h3 class="text-sm font-bold text-base-content">Tidak ada transaksi</h3>
                                <p class="text-xs text-base-content/60 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="block md:hidden divide-y divide-base-300">
                @forelse ($items as $item)
                <div class="p-4" x-data="{ open: false }">
                    <div class="flex items-center text-xs">
                        <span class="text-base-content/60 font-medium">
                            {{ \Carbon\Carbon::parse($item->transaction_date ?? $item->created_at)->format('d M Y') }}
                        </span>
                    </div>

                    <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="tx-actions-{{ $item->id }}"
                            class="w-full flex items-center gap-3 pt-1 text-left group">
                        @php
                            $cv = $catVisuals[$item->category ?? ''] ?? $defaultCat;
                            $hasImg = !empty($item->image);
                            $pill = $pillFor($item->category ?? '');
                        @endphp
                        @if($hasImg)
                        <a href="{{ asset('storage/' . $item->image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $item->image) }}"
                                 loading="lazy"
                                 class="w-12 h-12 rounded-box object-cover border border-base-300"
                                 alt="Bukti transaksi">
                        </a>
                        @else
                        <div class="flex-shrink-0 w-12 h-12 {{ $pill }} border rounded-box flex items-center justify-center"
                             title="{{ $item->category ?? 'Lainnya' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">{!! $cv['path'] !!}</svg>
                        </div>
                        @endif

                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-base-content truncate text-sm sm:text-base">
                                {{ $item->title ?? $item->nama ?? $item->kategori }}
                            </p>
                            <p class="text-[11px] font-medium text-base-content/60 mt-0.5">
                                {{ $item->category ?? 'Lainnya' }}
                            </p>
                        </div>

                        <div class="flex-shrink-0 flex flex-col items-end gap-1">
                            <p class="font-bold text-sm sm:text-base whitespace-nowrap privacy-target {{ ($item->type ?? 'income') == 'income' ? 'text-success' : 'text-error' }}"
                               data-amount="{{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} {{ \App\Services\AmountFormatter::compact($item->amount ?? $item->nominal ?? 0) }}">
                                {{ ($item->type ?? 'income') == 'income' ? '+' : '−' }} {{ \App\Services\AmountFormatter::compact($item->amount ?? $item->nominal ?? 0) }}
                            </p>
                            <svg class="w-4 h-4 text-base-content/60 transition-transform duration-200"
                                 :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    <div id="tx-actions-{{ $item->id }}" x-show="open" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 -translate-y-1"
                         class="no-print flex flex-wrap items-center justify-end gap-2 pt-3 mt-3 border-t border-base-300">
                        @if(!empty($item->image))
                        <a href="{{ asset('storage/' . $item->image) }}" target="_blank" class="btn btn-ghost btn-xs gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <span>Bukti</span>
                        </a>
                        @endif
                        <a href="{{ route('transactions.edit', $item->id) }}" class="btn btn-ghost btn-xs gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit</span>
                        </a>

                        <form action="{{ route('transactions.destroy', $item->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" onclick="return confirm('Hapus transaksi ini?')" class="btn btn-primary btn-xs gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                <span>Hapus</span>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="py-16 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 bg-base-100 border border-base-300 text-base-content/60 rounded-box flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-base-content">Tidak ada transaksi</h3>
                    <p class="text-xs text-base-content/60 mt-1">Mulai catat transaksi pertamamu sekarang.</p>
                </div>
                @endforelse
            </div>

            @if(isset($transactions) && method_exists($transactions, 'links') && $transactions->hasPages())
            <div class="px-6 py-3 border-t border-base-300">
                {{ $transactions->links('vendor.pagination.tailwind') }}
            </div>
            @endif
