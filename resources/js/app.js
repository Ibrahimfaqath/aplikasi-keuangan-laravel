/**
 * APP.JS - Entry global DompetKu
 *
 * 1. Membundle Alpine.js (tidak lagi dari CDN) agar lebih cepat & konsisten.
 * 2. Theme core: mode Light / Dark (disimpan di localStorage 'theme').
 *    Default tetap dark (perilaku lama) bila belum ada pilihan tersimpan.
 *    Setiap perubahan disiarkan lewat event 'theme-changed' (dipakai grafik).
 * 3. Privacy mode: mask/unmask angka finansial (.privacy-target, .balance-text)
 *    lewat tombol [data-privacy-toggle]; ikon lock per tombol lewat
 *    [data-eye-open] (terbuka = terlihat) / [data-eye-closed] (terkunci).
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

// -----------------------------------------------------------------
// THEME CORE (vanilla, berlaku di semua halaman)
// -----------------------------------------------------------------
function getSavedTheme() {
    try {
        return localStorage.getItem('theme') || 'dark';
    } catch (e) {
        return 'dark';
    }
}

function effectiveDark(saved) {
    // Hanya mode Light / Dark. Nilai tersimpan lama 'system' jatuh ke dark (default lama).
    return saved !== 'light';
}

function syncThemeUI(saved) {
    try {
        document.querySelectorAll('[data-theme-option]').forEach((el) => {
            const on = el.getAttribute('data-theme-option') === saved;
            el.setAttribute('aria-checked', String(on));
            el.classList.toggle('theme-opt-active', on);
            const check = el.querySelector('[data-theme-check]');
            if (check) check.classList.toggle('hidden', !on);
        });
    } catch (e) {}
}

function applyTheme() {
    const saved = getSavedTheme();
    const isDark = effectiveDark(saved);
    document.documentElement.classList.toggle('dark', isDark);
    // Monochrome spec: light #FAFAFA, dark #0A0A0A
    document.documentElement.style.backgroundColor = isDark ? '#0A0A0A' : '#FAFAFA';
    // Beri tahu komponen lain (mis. grafik di dashboard) agar ikut menyesuaikan
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark } }));
    syncThemeUI(saved);
}

// Dipakai kontrol Appearance via onclick="window.setTheme('dark')" dsb.
window.setTheme = function (mode) {
    if (mode !== 'light' && mode !== 'dark') return;
    try {
        localStorage.setItem('theme', mode);
    } catch (e) {}
    applyTheme();
};

document.addEventListener('DOMContentLoaded', () => {
    applyTheme();

    // -----------------------------------------------------------------
    // PRIVACY MANAGEMENT (vanilla, berlaku di semua halaman)
    //    Mendukung beberapa tombol [data-privacy-toggle]; ikon gembok per
    //    tombol lewat [data-eye-open] / [data-eye-closed].
    //    Elemen saldo yang di-mask:
    //    - .balance-text  (data-value)  -> dashboard (layouts.app)
    //    - .privacy-target (data-amount) -> halaman transaksi (Alpine)
    // -----------------------------------------------------------------
    const privacyButtons = document.querySelectorAll('[data-privacy-toggle]');

    let isPrivate = false;
    try {
        isPrivate = localStorage.getItem('privacy_mode') === 'enabled';
    } catch (e) {}

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
            // Tombol murni ikon (tanpa teks, mis. di hero balance) memakai
            // aria-label dinamis; baris berlabel memakai teksnya sendiri.
            try {
                if (btn.textContent.trim().length === 0) {
                    btn.setAttribute('aria-label', isPrivate ? 'Show balance' : 'Hide balance');
                    btn.setAttribute('title', isPrivate ? 'Show balance' : 'Hide balance');
                }
            } catch (e) {}
        });
    }

    renderPrivacyUI();

    privacyButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            isPrivate = !isPrivate;
            try {
                localStorage.setItem('privacy_mode', isPrivate ? 'enabled' : 'disabled');
            } catch (e) {}
            renderPrivacyUI();
        });
    });

    // -----------------------------------------------------------------
    // START ALPINE — setelah listener vanilla terpasang
    // -----------------------------------------------------------------
    Alpine.start();

    // Re-terapkan mask privasi setelah Alpine selesai merender
    // (x-bind:data-amount / x-text baru tersedia setelah init, sehingga
    //  nilai yang sudah di-mask tidak tertimpa nilai asli oleh Alpine)
    requestAnimationFrame(() => requestAnimationFrame(() => renderPrivacyUI()));
});
