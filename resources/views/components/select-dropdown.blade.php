{{--
    Dropdown pilihan daisyUI (tombol + menu), pengganti <select> native.

    KENAPA bukan <select class="select">:
    dropdown native itu dirender oleh sistem operasi. Di aplikasi yang seluruh
   ardownya hitam pekat, begitu diklik yang muncul adalah daftar BERWARNA
    PUTIH milik OS -- satu-satunya bidang terang yang tidak bisa dikendalikan
    tema. `dropdown` + `menu` daisyUI dirender browser, jadi ikut gelap.

    Kontrak ke JS yang sudah ada (resources/js/app.js):
      - elemen pembawa nilai adalah <input type="hidden"> dengan `id` yang
        sama seperti <select> yang diganti, jadi `el.value` di
        syncFromUrl() dan tombol reset tetap bekerja tanpa perubahan.
      - event `change` dipakai untuk MEMICU filter (sama seperti
        <select> lama yang punya onchange="applyFilters()").
      - event `control:synced` (custom) dipakai hanya untuk menyinkronkan
        LABEL tombol saat `.value` di-set dari luar (syncFromUrl / tombol
        reset). Dipisah supaya sinkronisasi tidak ikut memicu request.

    CATATAN Tailwind: kelas di dalam <template>/Alpine harus string LITERAL di
    file Blade ini -- jangan dirangkai dari variabel, kalau tidak ter-purge.
--}}
@props([
    'id',
    'name',
    'value' => '',
    'options' => [],          // [ nilai => label ]
    'placeholder' => 'Pilih…',
    'label' => null,          // aria-label tombol
    'menuAlign' => 'left',
    'size' => 'sm',
    // Filter di halaman ini dipicu oleh atribut `onchange` pada tiap kontrol
    // (lihat resources/js/app.js). Karena pembawa nilainya sekarang
    // <input type="hidden">, atribut itu harus ikut dipasang -- tanpa itu
    // memilih opsi tidak menjalankan filter sama sekali.
    'onChange' => 'applyFilters()',
])

@php
    $optionsJs = collect($options)
        ->map(fn ($l, $v) => ['v' => (string) $v, 'l' => (string) $l])
        ->values()
        ->all();

    $alignClass = match ($menuAlign) {
        'right' => 'right-0',
        default => 'left-0',
    };

    // min-h wajib, bukan hanya h-*: `.select` daisyUI mengunci tinggi dengan
    // `min-height: 3rem`, jadi `h-10` saja kalah dan tombol jadi 48px --
    // tidak seragam dengan input & segoup di baris filter yang 40px.
    $btnSize = $size === 'md' ? 'h-12 min-h-12 text-base' : 'h-10 min-h-10 text-sm';
@endphp

<div class="dropdown w-full"
     x-data="{
         open: false,
         value: @js((string) $value),
         options: {{ Illuminate\Support\Js::from($optionsJs) }},
         label() {
             const found = this.options.find((o) => o.v === this.value);
             return found ? found.l : @js($placeholder);
         },
         choose(v) {
             this.value = v;
             this.open = false;
             const input = this.$refs.input;
             input.value = v;
             input.dispatchEvent(new Event('change', { bubbles: true }));
             this.$refs.trigger.focus();
         },
     }"
     @click.outside="open = false"
     @keydown.escape.window="open = false">

    <button type="button"
            x-ref="trigger"
            tabindex="0"
            class="select select-bordered {{ $btnSize }} w-full items-center justify-between gap-2 px-3 text-left font-normal"
            @click="open = !open"
            @keydown.down.prevent="open = true"
            :aria-expanded="open"
            @if ($label) aria-label="{{ $label }}" @endif>
        <span class="truncate" x-text="label()"></span>
        <svg class="w-4 h-4 shrink-0 opacity-60 transition-transform" x-bind:class="open && 'rotate-180'"
             fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    {{-- Pembawa nilai. ID-nya sengaja sama dengan <select> yang dulu, supaya
         app.js tidak perlu diubah. --}}
    <input type="hidden" x-ref="input" id="{{ $id }}" name="{{ $name }}" x-bind:value="value"
           onchange="{{ $onChange }}"
           @change="value = $event.target.value"
           @control:synced="value = $event.target.value">

    <ul tabindex="0"
        class="dropdown-content menu menu-sm {{ $alignClass }} z-30 mt-1 w-full max-h-72 overflow-y-auto
               bg-base-100 border border-base-300 rounded-box shadow-lg"
        x-show="open"
        x-cloak
        @keydown.escape="open = false">
        @foreach ($optionsJs as $option)
            <li>
                <button type="button"
                        class="flex items-center justify-between gap-3"
                        @click="choose(@js($option['v']))"
                        :class="value === @js($option['v']) && 'active'">
                    <span class="truncate">{{ $option['l'] }}</span>
                    <svg x-show="value === @js($option['v'])" class="w-3.5 h-3.5 shrink-0"
                         fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </button>
            </li>
        @endforeach
    </ul>
</div>