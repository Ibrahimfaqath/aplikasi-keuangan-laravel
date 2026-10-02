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

    // min-h wajib, bukan hanya h-*: `.select` daisyUI mengunci tinggi dengan
    // `min-height: 3rem`, jadi `h-10` saja kalah dan tombol jadi 48px --
    // tidak seragam dengan input & segoup di baris filter yang 40px.
    $btnSize = $size === 'md' ? 'h-12 min-h-12 text-base' : 'h-10 min-h-10 text-sm';
@endphp

{{-- PENTING: daisyUI tidak memakai `display` untuk membuka dropdown.
         `.dropdown-content` disembunyikan dengan visibility:hidden +
         opacity:0 + scale(.95), dan HANYA dibuka oleh
         `.dropdown.dropdown-open` (untuk pemicu dari JS) atau
         `.dropdown:focus-within` (untuk fokus/hover).

         Karena menu di sini dibuka lewat state Alpine -- bukan fokus tombol --
         WAJIB memasang `dropdown-open`. Dulu hanya `x-show`, hasilnya menu
         tetap tak terlihat: x-show cuma mengeset display, sedangkan
         visibility/opacity daisyUI tidak tersentuh. --}}
<div class="dropdown w-full"
     x-bind:class="open && 'dropdown-open'"
     x-data="{
         open: false,
         alignRight: false,
         value: @js((string) $value),
         options: {{ Illuminate\Support\Js::from($optionsJs) }},
         label() {
             const found = this.options.find((o) => o.v === this.value);
             return found ? found.l : @js($placeholder);
         },
         toggleMenu() {
             // Menutup dulu supaya bisa diukur dalam kondisi terbuka.
             if (this.open) { this.open = false; return; }

             this.open = true;
             this.$nextTick(() => {
                 const menu = this.$refs.menu;
                 const trigger = this.$refs.trigger;
                 if (!menu || !trigger) return;

                 // Kalau melebar ke kanan akan keluar dari layar,lundur ke
                 // KANAN tombol dan tumbuh ke kiri. Tanpa ini, menu di kolom
                 // paling kanan akan terpotong tepi layar.
                 const pad = 8;
                 this.alignRight = menu.getBoundingClientRect().right > window.innerWidth - pad;
             });
         },
         choose(v) {
             this.value = v;
             this.open = false;
             const input = this.$refs.input;
             input.value = v;
             input.dispatchEvent(new Event('change', { bubbles: true }));
             // Sengaja TIDAK mengembalikan fokus ke tombol: daisyUI membuka
             // `.dropdown-content` lewat `.dropdown:focus-within`, jadi memfokuskan
             // kembali tombol akan MEMBUKA lagi menunya tepat setelah ditutup.
         },
     }"
     @click.outside="open = false"
     @keydown.escape.window="open = false">

    <button type="button"
            x-ref="trigger"
            tabindex="0"
            class="select select-bordered bg-none {{ $btnSize }} w-full items-center justify-between gap-2 px-3 text-left font-normal"
            @click="toggleMenu()"
            @keydown.down.prevent="toggleMenu()"
            :aria-expanded="open"
            @if ($label) aria-label="{{ $label }}" @endif>
        <span class="truncate" x-text="label()"></span>
        {{-- Panah chevron sendiri. Panjang stroke 2 dengan viewBox 24 bikin
             ujungnya tajam dan ukurannya 14px supaya tidak bertabrakan dengan border
             dan tidak menggeser teks label. --}}
        <svg class="w-3.5 h-3.5 shrink-0 opacity-70 transition-transform duration-150"
             x-bind:class="open && 'rotate-180'"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             viewBox="0 0 24 24" aria-hidden="true">
            <path d="M6 9l6 6 6-6" />
        </svg>
    </button>

    {{-- Pembawa nilai. ID-nya sengaja sama dengan <select> yang dulu, supaya
         app.js tidak perlu diubah. --}}
    <input type="hidden" x-ref="input" id="{{ $id }}" name="{{ $name }}" x-bind:value="value"
           onchange="{{ $onChange }}"
           @change="value = $event.target.value"
           @control:synced="value = $event.target.value">

    <ul tabindex="0"
        {{-- max-h memakai min() supaya mengikuti tinggi viewport: di layar
             pendek menu tidak meluber melewati bawah layar, dan daftar tetap
             punya tempat untuk di-scroll. --}}
        x-ref="menu"
        class="dropdown-content menu menu-sm dropdown-menu z-30 mt-1
               min-w-[13rem] max-w-[min(20rem,calc(100vw-1.5rem))]
               max-h-[min(24rem,60vh)] overflow-y-auto dropdown-scroll
               bg-base-100 border border-base-300 rounded-box shadow-xl shadow-black/40"
        :class="alignRight ? 'right-0 left-auto' : 'left-0 right-auto'"
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