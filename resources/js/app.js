/**
 * APP.JS - Entry global dompetku
 *
 * 1. Membundle Alpine.js (tidak lagi dari CDN) agar lebih cepat & konsisten.
 * 2. Theme core: mode Light / Dark (disimpan di localStorage 'theme').
 *    Default tetap dark (perilaku lama) bila belum ada pilihan tersimpan.
 *    Setiap perubahan disiarkan lewat event 'theme-changed' (dipakai grafik).
 * 3. Privacy mode: mask/unmask angka finansial (.privacy-target, .balance-text)
 *    lewat tombol [data-privacy-toggle]; ikon GEMBOK per tombol lewat
 *    [data-lock-open] (muncul saat saldo TERLIHAT = gembok terbuka) /
 *    [data-lock-closed] (muncul saat saldo TERSEMBUNYI = gembok terkunci).
 *    Label teks sinkron via [data-privacy-label].
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
        // Set nilai dari luar (sinkronisasi form saat tombol Back / reset).
        // Sengaja tidak memanggil onchange supaya tidak memicu submit berulang.
        setValue(v) {
            this.value = v === null || v === undefined ? '' : String(v);
            this.close();
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

// ---------------------------------------------------------------------
// FILTER TANPA RELOAD
//
// Server tetap sumber kebenaran: halaman penuh tetap dirender PHP, dan
// `?partial=1` hanya mengembalikan potongan yang berubah (tabel, angka
// Ringkasan, data donat). Kalau JS mati atau fetch gagal, form tetap
// submit seperti biasa — tidak ada fitur yang hilang.
// ---------------------------------------------------------------------
(function () {
    const DEBOUNCE_MS = 350;
    const KEYS = ['search', 'type', 'category', 'period'];
    const SELECTS = { type: 'filterType', category: 'filterCategory', period: 'filterPeriod' };
    // Pilihan "Semua ..." = keadaan netral, dipakai oleh tombol reset dan
    // untuk membuang parameter kosong dari URL.
    const DEFAULTS = { search: '', type: '', category: '', period: 'all' };

    let seq = 0;        // penjaga race: hanya respons terakhir yang dipakai
    let timer = null;
    let inflight = null;

    const $ = (id) => document.getElementById(id);

    function selectData(id) {
        const el = $(id);
        return el && window.Alpine && window.Alpine.$data ? window.Alpine.$data(el) : null;
    }

    function currentParams() {
        const f = $('filterForm');
        if (!f) return new URLSearchParams();

        const fd = new FormData(f);
        // PENTING: jangan percaya hidden input untuk nilai dropdown.
        //
        // x-custom-select mengikat nilainya secara reaktif (`:value="value"`),
        // jadi yang tertulis di DOM baru menyusul di microtask berikutnya.
        // Label di layar sudah ikut berubah duluan (itu getter), tapi
        // FormData yang dibaca di sini masih membawa nilai SEBELUMNYA --
        // hasilnya: dropdown menulis "Pemasukan" sementara tabel, Ringkasan,
        // dan donat masih menampilkan data filter yang lama.
        //
        // Membaca langsung dari state Alpine menutup celah balapan itu, karena
        // nilainya sudah benar pada saat itu juga.
        for (const key in SELECTS) {
            const d = selectData(SELECTS[key]);
            fd.set(key, d && typeof d.value === 'string' ? d.value : DEFAULTS[key]);
        }

        // URL dijaga tetap ringkas: parameter yang kosong atau sama dengan
        // pilihan default tidak perlu ikut.
        const out = new URLSearchParams();
        for (const key of KEYS) {
            const v = (fd.get(key) || '').trim();
            if (v !== '' && v !== DEFAULTS[key]) out.set(key, v);
        }
        return out;
    }

    function hasFilters(p) {
        // 'all' adalah nilai default Periode, jadi bukan filter aktif. Kalau
        // tidak dicek di sini, tombol reset akan muncul begitu saja begitu satu
        // request terkirim (FormData selalu ikut membawa period=all).
        return (p.get('search') || '') !== ''
            || (p.get('type') || '') !== ''
            || (p.get('category') || '') !== ''
            || ((p.get('period') || '') !== '' && (p.get('period') || '') !== 'all');
    }

    function syncReset(p) {
        const btn = $('filterReset');
        if (btn) btn.classList.toggle('hidden', !hasFilters(p));
    }

    function syncControls(p) {
        const s = $('filterSearch');
        const wantSearch = p.get('search') || DEFAULTS.search;
        if (s && s.value !== wantSearch) s.value = wantSearch;

        for (const key in SELECTS) {
            // Tanpa `|| DEFAULTS[key]`, popstate ke URL yang tidak memuat
            // parameter akan mengembalikan Periode ke kosong, bukan "Semua
            // Waktu" seperti semula.
            const want = p.has(key) ? p.get(key) : DEFAULTS[key];
            const d = selectData(SELECTS[key]);
            if (d && typeof d.setValue === 'function' && d.value !== want) d.setValue(want);
        }
    }

    function apply(data) {
        const table = $('riwayatTable');
        if (table && typeof data.tableHtml === 'string') table.innerHTML = data.tableHtml;

        const count = $('riwayatCount');
        if (count) count.textContent = data.total + ' transaksi tercatat';

        const live = $('filterStatus');
        if (live) live.textContent = data.total + ' transaksi ditemukan.';

        window.dispatchEvent(new CustomEvent('filters-applied', { detail: data }));
    }

    function load(params, push) {
        const mine = ++seq;
        if (inflight) inflight.abort();
        const ctrl = new AbortController();
        inflight = ctrl;

        const table = $('riwayatTable');
        if (table) table.setAttribute('aria-busy', 'true');

        const qs = params.toString();
        const url = window.location.pathname + (qs ? '?' + qs : '');
        const endpoint = url + (qs ? '&' : '?') + 'partial=1';

        fetch(endpoint, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: ctrl.signal,
        })
            .then((r) => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then((data) => {
                if (mine !== seq) return;            // respons basi — buang
                apply(data);
                if (push) window.history.pushState({ q: qs }, '', url);
                syncReset(params);
            })
            .catch((err) => {
                if (err && err.name === 'AbortError') return;
                // Fallback ke submit biasa. Tunggu satu siklus Alpine dulu:
                // nilai dropdown baru saja berubah, dan hidden input-nya
                // baru tersinkron setelah Alpine selesai flush. Tanpa
                // penantian ini, form::__submit() membaca nilai lama.
                const fallback = function () {
                    const f = $('filterForm');
                    if (f) f.submit();
                };
                if (window.Alpine && typeof window.Alpine.nextTick === 'function') {
                    window.Alpine.nextTick(fallback);
                } else {
                    fallback();
                }
            })
            .finally(() => {
                if (mine === seq && table) table.removeAttribute('aria-busy');
            });
    }

    function run() {
        const params = currentParams();
        load(params, true);
    }

    // Dipanggil x-custom-select lewat prop `onchange`.
    window.applyFilters = function () {
        if (timer) { clearTimeout(timer); timer = null; }
        run();
    };

    function init() {
        const f = $('filterForm');
        if (!f) return;

        // Pencarian: debounce supaya tidak satu request per ketikan.
        const search = $('filterSearch');
        if (search) {
            search.addEventListener('input', function () {
                if (timer) clearTimeout(timer);
                timer = setTimeout(function () { timer = null; run(); }, DEBOUNCE_MS);
            });
        }

        // Reset: tanpa reload. form.reset() tidak menyentuh x-custom-select
        // karena nilainya diikat Alpine, bukan properti DOM. Setiap dropdown
        // dikembalikan ke pilihan "Semua ...", bukan ke string kosong, supaya
        // Periode kembali ke "Semua Waktu" dan tidak menyisakan nilai aneh.
        const reset = $('filterReset');
        if (reset) {
            reset.addEventListener('click', function (e) {
                e.preventDefault();
                if (timer) { clearTimeout(timer); timer = null; }
                if (search) search.value = DEFAULTS.search;
                for (const key in SELECTS) {
                    const d = selectData(SELECTS[key]);
                    if (d && typeof d.setValue === 'function') d.setValue(DEFAULTS[key]);
                }
                window.applyFilters();
            });
        }

        // Pagination: hanya link di dalam <nav role="navigation">, supaya link
        // lain di tabel (edit/hapus) dan header (Tambah/Sampah) tidak ikut
        // dicuri.
        const riwayat = $('riwayat');
        const nav = riwayat && riwayat.querySelector('nav[role="navigation"]');
        if (nav) {
            nav.addEventListener('click', function (e) {
                const a = e.target.closest('a[href]');
                if (!a || a.target === '_blank') return;
                const u = new URL(a.href, window.location.origin);
                if (u.origin !== window.location.origin) return;
                e.preventDefault();
                const p = new URLSearchParams(u.search);
                load(p, true);
            });
        }

        // Tombol Back / Forward: kembalikan seluruh state dari URL.
        window.addEventListener('popstate', function (e) {
            const q = (e.state && e.state.q) || window.location.search.replace(/^\?/, '');
            const p = new URLSearchParams(q);
            syncControls(p);
            syncReset(p);
            load(p, false);
        });

        syncReset(currentParams());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

document.addEventListener('DOMContentLoaded', () => {
    applyTheme();

    // Tombol theme toggle satu klik (navbar, sidebar, profile/edit).
    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => window.toggleTheme());
    });

    // -----------------------------------------------------------------
    // PRIVACY MANAGEMENT (vanilla, berlaku di semua halaman)
    //    Mendukung beberapa tombol [data-privacy-toggle]; ikon per tombol
    //    lewat [data-lock-open] (muncul saat saldo TERLIHAT = gembok
    //    terbuka) dan [data-lock-closed] (muncul saat saldo TERSEMBUNYI =
    //    gembok terkunci). Label teks sinkron lewat [data-privacy-label].
    //    Elemen saldo yang di-mask:
    //    - .balance-text   (data-value)   -> dashboard (layouts.app)
    //    - .privacy-target (data-amount)  -> halaman transaksi (Alpine)
    // -----------------------------------------------------------------
    function getPrivacyState() {
        try {
            return localStorage.getItem('privacy_mode') === 'enabled';
        } catch (e) {
            return false;
        }
    }

    function syncPrivacyButton(btn, isPrivate) {
        const lockOpen = btn.querySelector('[data-lock-open]');
        const lockClosed = btn.querySelector('[data-lock-closed]');
        lockOpen?.classList.toggle('hidden', isPrivate);
        lockOpen?.classList.toggle('block', !isPrivate);
        lockClosed?.classList.toggle('hidden', !isPrivate);
        lockClosed?.classList.toggle('block', isPrivate);
        const label = btn.querySelector('[data-privacy-label]');
        if (label) label.textContent = isPrivate ? 'Tampilkan Saldo' : 'Sembunyikan Saldo';
        btn.setAttribute('aria-label', isPrivate ? 'Tampilkan saldo' : 'Sembunyikan saldo');
        btn.setAttribute('title', isPrivate ? 'Tampilkan saldo' : 'Sembunyikan saldo');
    }

    function renderPrivacyUI() {
        const isPrivate = getPrivacyState();
        document.querySelectorAll('.balance-text').forEach((el) => {
            const realVal = el.getAttribute('data-value') || 'Rp 0';
            el.textContent = isPrivate ? '••••••••' : realVal;
        });
        document.querySelectorAll('.privacy-target').forEach((el) => {
            const realVal = el.getAttribute('data-amount') || 'Rp 0';
            el.textContent = isPrivate ? '••••••••' : realVal;
        });
        document.querySelectorAll('[data-privacy-toggle]').forEach((btn) => syncPrivacyButton(btn, isPrivate));
    }

    // Ikon langsung disinkronkan saat modul dieksekusi (deferred => DOM sudah
    // terurai) agar tombol privasi tidak pernah menampilkan ikon yang salah.
    renderPrivacyUI();

    document.querySelectorAll('[data-privacy-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const isPrivate = !getPrivacyState();
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
