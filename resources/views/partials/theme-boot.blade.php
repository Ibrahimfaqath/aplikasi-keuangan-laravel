{{--
    THEME BOOT — wajib ada di <head> setiap halaman, SEBELUM CSS dirender,
    supaya tidak ada flash mode yang salah (FOUC).

    Ditaruh sebagai partial, bukan script yang di-copy-paste per halaman:
    logika tema berubah sewaktu-waktu, dan 14 salinan pasti akan berbeda
    satu sama lain. Yang memakai partial ini: SEMUA halaman.

    Menyetel DUA atribut sekaligus, itu wajib:
      1. class="dark"              -> utility `dark:` dari Tailwind (darkMode: 'class')
      2. data-theme="light|black"  -> komponen daisyUI

    Hanya menyetel satu = setengah halaman tidak ikut ganti mode: utility
    `dark:` tetap jalan tapi `btn`/`card`/`input` masih warna terang, atau
    sebaliknya.

    Nilai 'light' dan 'black' HARUS sama dengan `daisyui.themes` di
    tailwind.config.js. Kalau tema daisyUI diganti, ubah DI SINI juga.

    Mode gelap = default (tidak mengikuti sistem), sesuai perilaku lama.
--}}
<script>
    (function () {
        var root = document.documentElement;
        try {
            var isDark = localStorage.getItem('theme') !== 'light';
            root.classList.toggle('dark', isDark);
            root.setAttribute('data-theme', isDark ? 'black' : 'lofi');
        } catch (e) {
            // localStorage diblokir (mode privat): tetap pastikan tema terpasang,
            // default gelap seperti asumsi awal.
            root.classList.add('dark');
            root.setAttribute('data-theme', 'black');
        }
    })();
</script>
