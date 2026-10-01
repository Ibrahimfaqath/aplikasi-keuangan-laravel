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

// Dipanggil dengan mode ('light'|'dark') setelah localStorage ditulis, atau
// TANPA argumen pada load pertama — di kasus itu partial theme-boot sudah
// menyelesaikan mode sebelum paint, jadi kita baca dari DOM.
//
// Penting: jangan menebak ulang dari localStorage di sini. Dulu applyTheme()
// membaca localStorage sendiri, padahal partial sudah menyetel .dark — dua
// sumber kebenaran. Kalau keduanya menyimpang, halaman berkedip dalam mode
// yang salah lalu melompat (flash yang justru paling ingin dihindari oleh boot script.
function applyTheme(mode) {
    const root = document.documentElement;
    const isDark = mode ? mode === 'dark' : root.classList.contains('dark');
    root.classList.toggle('dark', isDark);
    // Komponen daisyUI membaca `data-theme`, bukan kelas `.dark`.
    // Harus ikut diset di sini, bukan hanya di partial theme-boot (yang hanya
    // jalan sekali saat load) — kalau tidak, toggle akan terlihat seperti
    // tidak bekerja untuk btn/card/input.
    root.setAttribute('data-theme', isDark ? 'black' : 'lofi');
    // Latar halaman TIDAK di set manual di sini: daisyUI sudah memberi
    // background lewat `:root[data-theme]` (lihat app.css). Menulis
    // style.backgroundColor akan menimpa tema dan menyisakan sisa lighten.
    // Beri tahu komponen lain (mis. grafik di dashboard) agar ikut menyesuaikan
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark } }));
}

// Dipakai kontrol Appearance via onclick="window.setTheme('dark')" dsb.
window.setTheme = function (mode) {
    if (mode !== 'light' && mode !== 'dark') return;
    try {
        localStorage.setItem('theme', mode);
    } catch (e) {}
    applyTheme(mode);
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
    // Tipe TIDAK ada di sini: sejak diubah jadi segoup radio native, nilainya
    // diambil langsung dari FormData (sudah sinkron, tidak ada balapan Alpine).
    const SELECTS = { category: 'filterCategory', period: 'filterPeriod' };

    // <select> native sudah diganti <x-select-dropdown> (dropdown + menu daisyUI).
    // Elemen pembawa nilainya tetap <input type="hidden"> dengan id yang sama,
    // jadi baca/tulis `.value` di bawah tetap jalan tanpa mengubah alur filter.
    // Tapi label di tombol dropdown tidak ikut berubah kalau `.value` di-set
    // diam-diam -- karena itu penetapan nilai di sini mengirim `change`.
    function setControlValue(el, v) {
        el.value = v;
        // Custom event, BUKAN `change`: `change` dipakai komponen dropdown
        // untuk memicu filter, jadi menyinkronkan label tidak boleh ikut
        // mengirim request.
        el.dispatchEvent(new CustomEvent('control:synced', { bubbles: true }));
    }
    // Pilihan "Semua ..." = keadaan netral, dipakai oleh tombol reset dan
    // untuk membuang parameter kosong dari URL.
    const DEFAULTS = { search: '', type: '', category: '', period: 'all' };

    let seq = 0;        // penjaga race: hanya respons terakhir yang dipakai
    let timer = null;
    let inflight = null;

    const $ = (id) => document.getElementById(id);

    function currentParams() {
        const f = $('filterForm');
        if (!f) return new URLSearchParams();

        // Filter diambil apa adanya dari FormData. Dulu nilai dropdown harus
        // dibaca lewat Alpine.$data() karena x-custom-select mengikat nilainya
        // secara reaktif, sehingga DOM masih menyimpan nilai LAMA ketika
        // FormData dibaca — dropdown sudah menulis "Pemasukan" sementara tabel
        // masih menampilkan data filter sebelumnya. Setelah diganti <select>
        // native, .value selalu sudah benar saat form dibaca, jadi seluruh
        // workaround balapan itu (dan selectData()) bisa dihapus.
        const fd = new FormData(f);

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

    // Segoup radio Tipe butuh perlakuan sendiri: ia native, jadi cukup dengan
    // menyetel `checked`, tidak ada state Alpine yang perlu disinkronkan.
    function syncTypeRadio(want) {
        const f = $('filterForm');
        if (!f) return;
        f.querySelectorAll('input[type="radio"][name="type"]').forEach(function (r) {
            r.checked = r.value === (want || '');
        });
    }

    function syncControls(p) {
        const s = $('filterSearch');
        const wantSearch = p.get('search') || DEFAULTS.search;
        if (s && s.value !== wantSearch) s.value = wantSearch;

        syncTypeRadio(p.has('type') ? p.get('type') : DEFAULTS.type);

        for (const key in SELECTS) {
            // Tanpa `|| DEFAULTS[key]`, popstate ke URL yang tidak memuat
            // parameter akan mengembalikan Periode ke kosong, bukan "Semua
            // Waktu" seperti semula.
            const want = p.has(key) ? p.get(key) : DEFAULTS[key];
            const el = $(SELECTS[key]);
            if (el && el.value !== want) setControlValue(el, want);
        }
    }

    function apply(data) {
        const table = $('riwayatTable');
        if (table && typeof data.tableHtml === 'string') table.innerHTML = data.tableHtml;

        const count = $('riwayatCount');
        if (count) {
            // Kalimat default-nya milik dashboard. Halaman lain (mis. Sampah)
            // punya konteks berbeda, jadi kalimatnya bisa ditimpa lewat
            // data-count-suffix tanpa menambah cabang di sini.
            const suffix = count.dataset.countSuffix || ' transaksi tercatat';
            count.textContent = data.total + suffix;
        }

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

        // `partial` adalah penanda endpoint, bukan bagian dari state filter.
        // Link pagination dihasilkan dari query string request parsial, jadi
        // ia ikut membawa `partial=1`. Kalau tidak dibuang, URL yang di-push
        // ke history (dan yang dishare user) akan memicu render JSON saat
        // dibuka ulang sebagai halaman penuh.
        const clean = new URLSearchParams(params);
        clean.delete('partial');

        const qs = clean.toString();
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

    // Dipanggil atribut onchange pada `<select>` dan `<input>` filter.
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

        // Reset: tanpa reload. Setiap kontrol dikembalikan ke pilihan
        // "Semua ...", bukan ke string kosong, supaya Periode kembali ke
        // "Semua Waktu" dan tidak menyisakan nilai aneh di URL.
        const reset = $('filterReset');
        if (reset) {
            reset.addEventListener('click', function (e) {
                e.preventDefault();
                if (timer) { clearTimeout(timer); timer = null; }
                if (search) search.value = DEFAULTS.search;
                syncTypeRadio(DEFAULTS.type);
                for (const key in SELECTS) {
                    const el = $(SELECTS[key]);
                    if (el) setControlValue(el, DEFAULTS[key]);
                }
                window.applyFilters();
            });
        }

        // Pagination: hanya link di dalam <nav role="navigation">, supaya link
        // lain di tabel (edit/hapus) dan header (Tambah/Sampah) tidak ikut
        // dicuri.
        //
        // Listener dipasang pada wadah #riwayat (delegasi), bukan pada <nav>-nya.
        // Setiap filter mengganti isi #riwayatTable lewat innerHTML, sehingga <nav>
        // lama ikut terbuang beserta listener-nya -- tanpa delegasi, pagination
        // berikutnya jatuh ke navigasi penuh dan menampilkan JSON `partial=1`.
        const riwayat = $('riwayat');
        if (riwayat) {
            riwayat.addEventListener('click', function (e) {
                const target = e.target && e.target.closest ? e.target : null;
                const a = target && target.closest('nav[role="navigation"] a[href]');
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
