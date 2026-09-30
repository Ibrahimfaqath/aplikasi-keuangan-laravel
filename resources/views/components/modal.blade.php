@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
])

@php
    // daisyUI 4 tidak punya modifier ukuran untuk modal, jadi lebar tetap pakai
    // utility max-w Tailwind di atas modal-box. `sm:max-w-*` menimpa nilai
    // default `.modal-box` (max-width: 32rem) pada layar >= sm.
    $maxWidth = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
    ][$maxWidth] ?? '';
@endphp

{{--
    Modal dibangun di atas komponen `modal` daisyUI.

    DaisyUI menyembunyikan `.modal` secara bawaan dan menampilkannya saat
    elemen itu punya kelas `modal-open`. Karena visibilitasnya ditangani
    Alpine (bukan `<dialog>`/checkbox bawaan daisyUI), yang kita lakukan
    hanya toggle `modal-open` — sisanya (posisi tengah, radius, warna
    surface, animasi pop) semuanya milik daisyUI.

    CATATAN SCIM: daisyUI 4 tidak menyediakan overlay gelap untuk modal yang
    dibuka lewat kelas `.modal-open` (`.modal-backdrop` hanya `color:
    transparent` untuk elemen `<form method="dialog">`). Karena itu overlay
    kita isi sendiri. Warnanya `neutral-950` — sengaja TIDAK memakai
    `base-content`, karena di tema gelap base-content itu terang
    (#e5e5e5) dan scrim-nya akan terbalik. Overlay gelap harus gelap
    di kedua mode, jadi dia tidak boleh ikut tema.

    Pemanggil tidak berubah: tetap `@dispatch('open-modal', 'nama')` /
    `@dispatch('close-modal', 'nama')`.

    `p-0` pada modal-box karena isi slot sudah membawa padding sendiri
    (p-5 sm:p-6), supaya tidak dobel.
--}}
<div
    x-data="{
        show: @js($show),
        focusables() {
            const selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])';
            return [...$el.querySelectorAll(selector)]
                .filter(el => !el.hasAttribute('disabled'));
        },
        firstFocusable() { return this.focusables()[0] },
        lastFocusable() { return this.focusables().slice(-1)[0] },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable() },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable() },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1) },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1 },
    }"
    x-cloak
    x-init="$watch('show', value => {
        if (value) {
            document.body.classList.add('overflow-y-hidden');
            {{ $attributes->has('focusable') ? 'setTimeout(() => firstFocusable().focus(), 100)' : '' }}
        } else {
            document.body.classList.remove('overflow-y-hidden');
        }
    })"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    x-on:keydown.tab.prevent="$event.shiftKey || nextFocusable().focus()"
    x-on:keydown.shift.tab.prevent="prevFocusable().focus()"
    class="modal"
    :class="{ 'modal-open': show }"
    role="dialog"
    aria-modal="true"
>
    <div class="modal-box p-0 {{ $maxWidth }}">
        {{ $slot }}
    </div>

    {{-- Overlay + penutup klik-tubuh. `modal-backdrop` membentang penuh di dalam
         grid `.modal`, jadi klik di luar kotak tetap kena elemen ini. --}}
    <div class="modal-backdrop bg-neutral-950/60 backdrop-blur-sm" x-on:click="show = false" aria-hidden="true"></div>
</div>
