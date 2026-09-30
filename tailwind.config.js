import daisyui from 'daisyui';
import daisyuiThemes from 'daisyui/src/theming/themes.js';

/**
 * DOMPETKU — DAFTAR TEMA daisyUI
 * ------------------------------------------------------------------
 * Dua tema, keduanya monokrom, spread dari tema bawaan daisyUI lalu
 * di-override seperlunya (bukan ditulis ulang) supaya perbaikan bawaan
 * tetap terbawa saat daisyUI di-upgrade.
 *
 * Angka di bawah sengaja disamakan dengan desain yang sebelumnya dipakai
 * (lihat git log / DEPLOY-CPANEL.md), supaya migrasi ini tidak mengubah
 * tampilan gelap secara berarti.
 */

// Terang. `lofi` = monokrom dengan tombol hampir hitam (#0D0D0D),
// jadi paling dekat dengan karakter app ini. RADIUS DIPATIH 0 supaya
// sama dengan tema gelap — kalau tidak, gaya sudut ikut berubah saat
// user toggle tema.
const lofi = {
    ...daisyuiThemes.lofi,
    '--rounded-box': '0',
    '--rounded-btn': '0',
    '--rounded-badge': '0',
    '--tab-radius': '0',
};

// Gelap. Dua koreksi penting dari tema `black` bawaan:
//  1. base-100 > base-200 (kartu lebih TERANG dari latar).
//     Bawaannya kebalik (#000 < #141414) sehingga kartu terlihat "tenggelam".
//  2. Semantic color dinormalkan ke pastel yang sama dengan `lofi`.
//     Bawaannya neon murni (#008000 / #ffff00 / #ff0000) dan mode terang
//     akan jadi karakter yang berbeda.
const black = {
    ...daisyuiThemes.black,
    'base-100': '#1c1c1c', // surface kartu  (sebelumnya #171717)
    'base-200': '#0d0d0d', // latar halaman (sebelumnya #0A0A0A)
    'base-300': '#2e2e2e', // border + hover (sebelumnya #333333)
    'base-content': '#e5e5e5', // teks utama    (sebelumnya #FAFAFA)
    // Samakan dengan palet `lofi` — kalem, bukan menyala.
    info: daisyuiThemes.lofi.info,
    success: daisyuiThemes.lofi.success,
    warning: daisyuiThemes.lofi.warning,
    error: daisyuiThemes.lofi.error,
};


/** @type {import('tailwindcss').Config} */
export default {
    // Dark mode berbasis class, palette monochrome (spec: #0A0A0A / #171717 / #262626)
    darkMode: 'class',

    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        // Peta warna kategori (kelas bg-{warna}-50 dst.) hidup di
        // app/Support/CategoryStyle.php supaya bisa dipakai migration,
        // seeder, dan Blade sekaligus. Tanpa glob ini Tailwind akan
        // purge seluruh kelas itu dan kartu kategori kehilangan warna.
        './app/**/*.php',
    ],
    theme: {
        extend: {
            colors: {
                // Alias semantik monochrome agar konsisten di seluruh UI.
                // Light: bg #FAFAFA, card #FFFFFF, text #111111
                // Dark: bg #0A0A0A, card #171717, secondary #262626, border #333333
                ink: {
                    DEFAULT: '#111111',
                    soft: '#171717',
                    muted: '#262626',
                },
            },
            boxShadow: {
                // Shadow sangat subtle — prioritaskan border + whitespace
                card: '0 1px 2px 0 rgb(0 0 0 / 0.04)',
            },
        },
    },
    plugins: [daisyui],
    daisyui: {
        // Definisi lengkap (lihat blok di atas): tema spread dari bawaan daisyUI
        // lalu di-override. WAJIB dibungkus nama — `themes: [{ nama: {...} }]`.
        // Kalau objek tema-nya ditulis polos (`[lofi, black]`), daisyUI akan
        // menganggap setiap KEY di dalamnya (primary, color-scheme, ...) sebagai
        // nama tema dan menghasilkan selector rusak.
        // Nama harus sama dengan yang diset theme-boot partial (`data-theme`)
        // dan oleh app.js saat toggle.
        themes: [{ lofi }, { black }],
        // Dipakai daisyUI untuk fallback `@media (prefers-color-scheme: dark)`.
        // Kita selalu menyetel data-theme secara eksplisit, jadi ini hanya
        // jaring pengaman bila JS belum jalan.
        darkTheme: 'black',
        // base: true  -> daisyUI menyetel background/color di :root & [data-theme].
        //                Aturan kita di app.css HARUS memakai selektor dengan
        //                spesifisitas yang sama (:root/[data-theme]), karena
        //                `:root` (0,1,0) mengalahkan `html` (0,0,1).
        base: true,
        styled: true,
        utils: true,
        logs: false,
    },
};

/**
 * DOMPETKU DESIGN TOKENS
 * ------------------------------------------------------------------
 * Sumber kebenaran warna sekarang adalah THEME daisyUI, bukan hex manual.
 * Lihat blok `daisyui` di atas: themes ['light', 'black'].
 *
 * PERMUKAAN (pakai token — otomatis ganti saat tema berubah, TANPA `dark:`):
 *   halaman/latar      bg-base-200
 *   card / panel       bg-base-100   + border border-base-300
 *   input / select     bg-base-100   + border border-base-300
 *   teks utama         text-base-content
 *   teks sekunder      text-base-content/60
 *   hover / surface    bg-base-300
 *
 * SEMANTIK (hanya untukmakna, ~15% UI):
 *   primary   aksi utama, tautan, progres
 *   success   pemasukan, anggaran aman, dalam target
 *   warning   mendekati batas anggaran, perlu perhatian
 *   error     pengeluaran over-budget, aksi destruktif
 *   info      informasi netral
 *
 * ATURAN:
 *   1. Permukaan TIDAK BOLEH memakai dark:bg-[#...] atau hex arbitrer.
 *      Satu token menutup dua mode sekaligus. Ini alasan utama adopsi daisyUI.
 *   2. Card tetap bg-base-100 + border; JANGAN warnai seluruh card.
 *   3. Amount/nominal selalu netral (text-base-content); warna hanya accent.
 *   4. Warna kategori (income/expense per kategori) tetap milik
 *      app/Support/CategoryStyle.php — itu data domain, bukan tema UI.
 *
 * MODE GELAP (dua mekanisme, keduanya wajib diset berbaruan oleh theme boot):
 *   .dark        -> utility `dark:` Tailwind (darkMode: 'class')
 *   data-theme   -> komponen daisyUI (value: 'light' | 'black')
 *   Hapus salah satu = setengah halaman tidak ikut ganti mode.
 */

