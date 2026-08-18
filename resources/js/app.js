/**
 * APP.JS - Entry global DompetKu
 *
 * 1. Membundle Alpine.js (tidak lagi dari CDN) agar lebih cepat & konsisten.
 * 2. Handler tunggal untuk tema (dark/light) & mode privasi — mendukung
 *    BANYAK tombol (navbar drawer + halaman profil) lewat data-attribute.
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    // -----------------------------------------------------------------
    // 1. THEME MANAGEMENT (vanilla, berlaku di semua halaman)
    //    Mendukung beberapa tombol [data-theme-toggle].
    //    Ikon matahari/bulan dikendalikan CSS (dark:) di tombol masing-masing.
    // -----------------------------------------------------------------
    const themeButtons = document.querySelectorAll('[data-theme-toggle]');

    themeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            document.documentElement.style.backgroundColor = isDark ? '#0A1128' : '#f8fafc';
            // Beri tahu komponen lain (mis. grafik di dashboard) agar ikut menyesuaikan
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark } }));
        });
    });

    // -----------------------------------------------------------------
    // 2. PRIVACY MANAGEMENT (vanilla, berlaku di semua halaman)
    //    Mendukung beberapa tombol [data-privacy-toggle]; ikon mata per
    //    tombol lewat [data-eye-open] / [data-eye-closed].
    //    Elemen saldo yang di-mask:
    //    - .balance-text  (data-value)  -> dashboard (layouts.app)
    //    - .privacy-target (data-amount) -> halaman transaksi (Alpine)
    // -----------------------------------------------------------------
    const privacyButtons = document.querySelectorAll('[data-privacy-toggle]');

    let isPrivate = localStorage.getItem('privacy_mode') === 'enabled';

    function renderPrivacyUI() {
        document.querySelectorAll('.balance-text').forEach((el) => {
            const realVal = el.getAttribute('data-value') || 'Rp 0';
            el.textContent = isPrivate ? '••••••••' : realVal;
        });

        document.querySelectorAll('.privacy-target').forEach((el) => {
            const realVal = el.getAttribute('data-amount') || 'Rp 0';
            el.textContent = isPrivate ? '••••••••' : realVal;
        });

        privacyButtons.forEach((btn) => {
            const eyeOpen = btn.querySelector('[data-eye-open]');
            const eyeClosed = btn.querySelector('[data-eye-closed]');
            eyeOpen?.classList.toggle('hidden', isPrivate);
            eyeOpen?.classList.toggle('block', !isPrivate);
            eyeClosed?.classList.toggle('hidden', !isPrivate);
            eyeClosed?.classList.toggle('block', isPrivate);
        });
    }

    renderPrivacyUI();

    privacyButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            isPrivate = !isPrivate;
            localStorage.setItem('privacy_mode', isPrivate ? 'enabled' : 'disabled');
            renderPrivacyUI();
        });
    });

    // -----------------------------------------------------------------
    // 3. START ALPINE — setelah listener vanilla terpasang
    // -----------------------------------------------------------------
    Alpine.start();

    // Re-terapkan mask privasi setelah Alpine selesai merender
    // (x-bind:data-amount / x-text baru tersedia setelah init, sehingga
    //  nilai yang sudah di-mask tidak tertimpa nilai asli oleh Alpine)
    requestAnimationFrame(() => requestAnimationFrame(() => renderPrivacyUI()));
});
