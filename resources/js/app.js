/**
 * APP.JS - Entry global DompetKu
 *
 * 1. Membundle Alpine.js (tidak lagi dari CDN) agar lebih cepat & konsisten.
 * 2. Handler tunggal untuk tema (dark/light) & mode privasi — dipakai oleh
 *    komponen navbar di SEMUA halaman (dashboard, transaksi, profil, dll).
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('DOMContentLoaded', () => {
    // -----------------------------------------------------------------
    // 1. THEME MANAGEMENT (vanilla, berlaku di semua halaman)
    //    Ikon matahari/bulan dikendalikan CSS (dark:) di komponen navbar.
    // -----------------------------------------------------------------
    const themeBtn = document.getElementById('theme-toggle');

    themeBtn?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.documentElement.style.backgroundColor = isDark ? '#111827' : '#f8fafc';
        // Beri tahu komponen lain (mis. grafik di dashboard) agar ikut menyesuaikan
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark } }));
    });

    // -----------------------------------------------------------------
    // 2. PRIVACY MANAGEMENT (vanilla, berlaku di semua halaman)
    //    Mendukung dua jenis elemen saldo:
    //    - .balance-text  (data-value)  -> dashboard (layouts.app)
    //    - .privacy-target (data-amount) -> halaman transaksi (Alpine)
    // -----------------------------------------------------------------
    const privacyBtn = document.getElementById('privacy-toggle-btn');
    const eyeOpen = document.getElementById('privacy-eye-open');
    const eyeClosed = document.getElementById('privacy-eye-closed');

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

        eyeOpen?.classList.toggle('hidden', isPrivate);
        eyeOpen?.classList.toggle('block', !isPrivate);
        eyeClosed?.classList.toggle('hidden', !isPrivate);
        eyeClosed?.classList.toggle('block', isPrivate);
    }

    renderPrivacyUI();

    privacyBtn?.addEventListener('click', () => {
        isPrivate = !isPrivate;
        localStorage.setItem('privacy_mode', isPrivate ? 'enabled' : 'disabled');
        renderPrivacyUI();
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
