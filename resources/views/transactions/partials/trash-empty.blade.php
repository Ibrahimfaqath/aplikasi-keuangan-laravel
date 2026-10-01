{{-- Empty state halaman Sampah.

     Dua kondisi yang tadinya memakai pesan yang sama:
       1. Sampah memang kosong        → ajakan kembali ke daftar transaksi
       2. Filter aktif tapi 0 hasil   → ajakan RESET filter, bukan "k Sampah kosong"

     Bedanya penting: nomor 2 bikin user mengira datanya hilang, padahal
     masih ada -- hanya tidak cocok dengan filter.

     Butuh: $hasActiveFilters (bool), $compact (bool — dirender di dalam <td> tabel) --}}

<div class="mx-auto {{ $compact ?? false ? 'max-w-sm' : 'max-w-md' }}">
    <div class="w-12 h-12 mx-auto mb-3 rounded-xl flex items-center justify-center
                {{ ($hasActiveFilters ?? false)
                    ? 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400'
                    : 'bg-base-200 text-base-content/60' }}">
        @if ($hasActiveFilters ?? false)
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
        @else
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
        @endif
    </div>

    @if ($hasActiveFilters ?? false)
        <h3 class="text-sm font-bold text-base-content">Tidak ada yang cocok</h3>
        <p class="text-xs text-base-content/60 mt-1">
            Tidak ada transaksi di Sampah yang cocok dengan filter aktif.
            Data kamu tetap aman — coba longgarkan filter untuk melihatnya.
        </p>
        <a href="{{ route('transactions.trashed') }}"
           class="inline-flex items-center gap-1.5 mt-4 px-3.5 py-2 bg-base-content hover:bg-base-content/80 text-base-100 rounded-xl text-xs font-semibold transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            Reset filter
        </a>
    @else
        <h3 class="text-sm font-bold text-base-content">Sampah kosong</h3>
        <p class="text-xs text-base-content/60 mt-1">
            Belum ada transaksi yang dihapus. Kalau ada, bakalan muncul di sini dan masih bisa dipulihkan.
        </p>
        <a href="{{ route('transactions.index') }}"
           class="inline-flex items-center gap-1.5 mt-4 px-3.5 py-2 bg-base-200 hover:bg-base-300 hover:bg-base-content/10 text-base-content/80 rounded-xl text-xs font-semibold transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Lihat transaksi
        </a>
    @endif
</div>
