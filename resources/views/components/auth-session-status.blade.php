@props(['status'])

@if ($status)
    {{-- Bentuk & tata letak dari `alert` daisyUI, warna dari token semantic
         (pola yang sama dengan components/flash.blade.php: modifier alert-*
         mencampur warna dengan base-100 sehingga kontrasnya sulit diprediksi
         di mode gelap). Ikon diberi wrapper karena `alert` memakai grid —
         teks polos akan jadi anonymous grid item dan tidak membungkus. --}}
    <div {{ $attributes->merge(['class' => 'alert gap-2 border border-info/30 bg-info/10 px-3 py-2 text-sm font-medium text-base-content']) }}>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div class="min-w-0">{{ $status }}</div>
    </div>
@endif
