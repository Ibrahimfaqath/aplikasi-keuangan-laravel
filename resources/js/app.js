/**
 * APP.JS - Legacy JavaScript untuk kompatibilitas
 * 
 * NOTE: Untuk halaman index.blade.php sudah menggunakan Alpine.js
 * File ini hanya digunakan untuk halaman-halaman lain yang belum migrasi ke Alpine
 */

document.addEventListener('DOMContentLoaded', () => {
    // -----------------------------------------------------------------
    // 1. THEME MANAGEMENT - Hanya jika belum di-handle oleh Alpine
    // -----------------------------------------------------------------
    const themeBtn = document.getElementById('theme-toggle');
    const darkIcon = document.getElementById('theme-toggle-dark-icon');
    const lightIcon = document.getElementById('theme-toggle-light-icon');

    function updateThemeIcons() {
        const isDark = document.documentElement.classList.contains('dark');
        if (isDark) {
            lightIcon?.classList.remove('hidden');
            darkIcon?.classList.add('hidden');
        } else {
            darkIcon?.classList.remove('hidden');
            lightIcon?.classList.add('hidden');
        }
    }
    updateThemeIcons();

    themeBtn?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.documentElement.style.backgroundColor = isDark ? '#111827' : '#f9fafb';
        updateThemeIcons();
    });

    // -----------------------------------------------------------------
    // 2. PRIVACY MANAGEMENT - Legacy support
    // -----------------------------------------------------------------
    const privacyBtn = document.getElementById('privacy-toggle-btn');
    const privacyText = document.getElementById('privacy-btn-text');
    const eyeOpen = document.getElementById('privacy-eye-open');
    const eyeClosed = document.getElementById('privacy-eye-closed');
    const balanceTexts = document.querySelectorAll('.balance-text');

    let isPrivate = localStorage.getItem('privacy_mode') === 'enabled';

    function renderPrivacyUI() {
        balanceTexts.forEach(el => {
            const realVal = el.getAttribute('data-value') || 'Rp 0';
            // FIX: Gunakan data-value, bukan langsung textContent
            // TextContent awal sudah masked, tapi kita update sesuai state
            el.textContent = isPrivate ? '••••••••' : realVal;
        });

        if (privacyText) {
            privacyText.textContent = isPrivate ? 'Tampilkan Saldo' : 'Sembunyikan Saldo';
        }

        if (isPrivate) {
            eyeOpen?.classList.add('hidden');
            eyeOpen?.classList.remove('block');
            eyeClosed?.classList.remove('hidden');
            eyeClosed?.classList.add('block');
        } else {
            eyeClosed?.classList.add('hidden');
            eyeClosed?.classList.remove('block');
            eyeOpen?.classList.remove('hidden');
            eyeOpen?.classList.add('block');
        }
    }

    // Terapkan UI Privasi
    renderPrivacyUI();

    privacyBtn?.addEventListener('click', () => {
        isPrivate = !isPrivate;
        localStorage.setItem('privacy_mode', isPrivate ? 'enabled' : 'disabled');
        renderPrivacyUI();
    });

    // -----------------------------------------------------------------
    // 3. SKELETON LOAD HYDRATION - FIXED
    // -----------------------------------------------------------------
    // PERBAIKAN: Skeleton sekarang di-handle oleh Alpine di index.blade.php
    // Untuk halaman lain yang masih pakai file ini, kita tetap support
    const skeletons = document.querySelectorAll('.skeleton-item');
    const contents = document.querySelectorAll('.content-item');

    // Cek apakah ada skeleton yang belum di-handle oleh Alpine
    // Jika ada, kita handle dengan loading state yang legitimate
    if (skeletons.length > 0 && contents.length > 0) {
        // Cek apakah ada data yang sudah dirender (indikasi server-side render)
        // Jika data sudah ada, kita langsung tampilkan content
        // Tapi dengan transisi yang smooth
        
        // Tapi hati-hati: di index.blade.php sudah pake Alpine, 
        // jangan double-handle
        
        // Solusi: cek apakah ada Alpine di halaman
        const hasAlpine = window.Alpine !== undefined;
        if (!hasAlpine) {
            // Halaman tanpa Alpine, handle dengan setTimeout yang lebih cerdas
            // BUKAN fake loading, tapi legitimate initial render wait
            setTimeout(() => {
                skeletons.forEach(el => {
                    el.classList.add('loaded');
                    el.style.display = 'none';
                });
                contents.forEach(el => {
                    el.classList.remove('hidden');
                    el.style.display = '';
                });
            }, 100); // Minimal time for initial paint
        }
    }
});