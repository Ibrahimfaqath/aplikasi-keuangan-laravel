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
// CUSTOM SELECT (Alpine data factory untuk <x-custom-select>).
// Pengganti <select> native agar daftar pilihan bisa di-style penuh,
// tetap ramah keyboard + screen reader (roles listbox/option).
// Dipakai via x-data="customSelect({options, selected, ...})".
// -----------------------------------------------------------------
window.customSelect = function (config) {
    const list = Object.entries(config.options || {}).map(([value, label]) => ({
        value: String(value),
        label: String(label),
    }));

    return {
        open: false,
        value: String(config.selected ?? ''),
        filter: '',
        activeIndex: -1,
        dropUp: false,
        searchable: !!config.searchable,
        onchange: config.onchange || '',
        options: list,
        typeBuffer: '',
        typeTimer: null,

        get selectedLabel() {
            const found = this.options.find((o) => o.value === this.value);
            return found ? found.label : 'Pilih…';
        },
        get filtered() {
            const q = this.filter.trim().toLowerCase();
            if (!q) return this.options;
            return this.options.filter((o) => o.label.toLowerCase().includes(q));
        },

        toggle() {
            if (this.open) this.close();
            else this.openPanel();
        },
        openPanel() {
            this.open = true;
            this.filter = '';
            const idx = this.options.findIndex((o) => o.value === this.value);
            this.activeIndex = idx >= 0 ? idx : 0;
            this.$nextTick(() => {
                // Auto-flip: buka ke atas bila ruang bawah tidak cukup
                // (mis. dropdown di dalam modal dekat bawah layar).
                try {
                    const r = this.$refs.trigger.getBoundingClientRect();
                    const need = Math.min(224, this.filtered.length * 46 + 16);
                    this.dropUp = window.innerHeight - r.bottom < need && r.top > need;
                } catch (e) {
                    this.dropUp = false;
                }
                if (this.searchable && this.$refs.search) this.$refs.search.focus();
                this.scrollActiveIntoView();
            });
        },
        close(refocus) {
            this.open = false;
            this.filter = '';
            this.activeIndex = -1;
            if (refocus && this.$refs.trigger) this.$refs.trigger.focus();
        },
        choose(opt) {
            if (!opt) return;
            this.value = opt.value;
            this.close(true);
            // Hook opsional: nama fungsi global, mis. "toggleCustomDates" di modal export.
            if (this.onchange && typeof window[this.onchange] === 'function') {
                window[this.onchange](this.value);
            }
        },
        move(dir) {
            if (!this.open) {
                this.openPanel();
                return;
            }
            const n = this.filtered.length;
            if (!n) return;
            this.activeIndex = (((this.activeIndex + dir) % n) + n) % n;
            this.scrollActiveIntoView();
        },
        chooseActive() {
            const list = this.filtered;
            if (!list.length) return;
            if (this.activeIndex < 0 || this.activeIndex >= list.length) this.activeIndex = 0;
            this.choose(list[this.activeIndex]);
        },
        scrollActiveIntoView() {
            this.$nextTick(() => {
                try {
                    const el = this.$refs.list?.querySelector('[data-index="' + this.activeIndex + '"]');
                    if (el && typeof el.scrollIntoView === 'function') {
                        el.scrollIntoView({ block: 'nearest' });
                    }
                } catch (e) {}
            });
        },
        // Type-ahead untuk dropdown non-searchable: ketik huruf untuk lompat ke opsi.
        typeAhead(e) {
            const key = e && e.key ? e.key : '';
            if (key.length !== 1 || key === ' ') return;
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            if (this.searchable && document.activeElement === this.$refs.search) return;
            if (!this.open) this.openPanel();
            this.typeBuffer = (this.typeBuffer + key).toLowerCase().slice(-12);
            clearTimeout(this.typeTimer);
            this.typeTimer = setTimeout(() => {
                this.typeBuffer = '';
            }, 600);
            const idx = this.filtered.findIndex((o) => o.label.toLowerCase().startsWith(this.typeBuffer));
            if (idx >= 0) {
                this.activeIndex = idx;
                this.scrollActiveIntoView();
            }
        },
    };
};

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

// Toggle langsung Light <-> Dark untuk [data-theme-toggle] (tanpa dropdown).
window.toggleTheme = function () {
    window.setTheme(effectiveDark(getSavedTheme()) ? 'light' : 'dark');
};

document.addEventListener('DOMContentLoaded', () => {
    applyTheme();

    // Tombol theme toggle satu klik (navbar, sidebar, profile/edit).
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => window.toggleTheme());
    });

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
