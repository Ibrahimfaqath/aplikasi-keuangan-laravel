@props([
    // Flash `status` dari form profil (profile-updated / password-updated).
    'status' => null,
])

{{--
    Notifikasi flash — SATU komponen untuk seluruh aplikasi.

    Sebelumnya setiap halaman menyalin sendiri blok toast inline (sekitar 10
    kali), lengkap dengan ikon, warna, dan timer yang berbeda-beda. Sekarang
    semuanya lewat daisyUI `toast` (posisi) + `alert` (warna & bentuk).

    Isi yang dirender otomatis:
      session('success')  -> alert-success, auto-hide 5 detik
      session('error')    -> alert-error,   auto-hide 8 detik
      $status             -> alert-success

    Slot `undo` dipakai halaman Sampah untuk tombol "Urungkan" (kirim balik
    transaksi ke Sampah). Slot itu hanya dirender kalau flash `undo_restore`
    benar-benar ada, supaya halaman lain tidak perlu logam sama sekali.

    Pemakaian:
      <x-flash />
      <x-flash :status="session('profile-updated')" />
      <x-flash><x-slot:undo> ...form... </x-slot:undo></x-flash>
--}}

@php
    // PENTING: nama kelas daisyUI ditulis LITERAL, bukan dirangkai dari variabel
    // (`alert-{{ $variant }}`). Tailwind memindai file sumber sebagai TEKS, jadi
    // kelas yang dirangkai tidak pernah terlihat dan ikut ter-purge — komponennya
    // jadi tanpa warna sama sekali. Karena itu di sini disimpan nama kelas utuh.
    $alertClass = [
        'success' => 'alert alert-success',
        'error' => 'alert alert-error',
    ];

    $items = [];

    if (session('success')) {
        $items[] = ['variant' => 'success', 'message' => session('success'), 'ms' => 5000];
    }

    if (session('error')) {
        $items[] = ['variant' => 'error', 'message' => session('error'), 'ms' => 8000];
    }

    if ($status) {
        $items[] = [
            'variant' => 'success',
            'message' => $status === 'password-updated' ? 'Kata sandi berhasil diperbarui.' : 'Profil berhasil diperbarui.',
            'ms' => 4000,
        ];
    }

    $hasUndo = session('undo_restore') && ! $undo->isEmpty();
@endphp

@if ($items)
    <div class="toast toast-top toast-end z-50 mt-20 w-full max-w-sm space-y-2">
        @foreach ($items as $item)
            <div
                class="{{ $alertClass[$item['variant']] }} border shadow-sm"
                x-data="{ show: true }"
                x-show="show"
                x-cloak
                x-init="setTimeout(() => show = false, {{ $hasUndo ? 12000 : $item['ms'] }})"
            >
                @if ($item['variant'] === 'success')
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                @endif

                <div class="min-w-0">
                    <p class="text-xs font-semibold">{{ $item['message'] }}</p>

                    @if ($hasUndo)
                        {{-- "Urungkan" = kirim balik ke Sampah. Logikanya berbalik
                             dengan pemulihan, jadi bunyinya menjelaskan itu,
                             bukan sekadar "Undo" yang kabur. --}}
                        <div class="mt-2 flex items-center gap-2">
                            {{ $undo }}
                        </div>
                    @endif
                </div>

                <button type="button" class="btn btn-ghost btn-xs btn-circle -mr-1 shrink-0" x-on:click="show = false" aria-label="Tutup notifikasi">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endforeach
    </div>
@endif
