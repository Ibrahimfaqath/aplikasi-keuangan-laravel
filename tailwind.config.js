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

// Radius dipakai bersama oleh kedua tema. Nilai diambil dari tema `lofi`
// bawaan daisyUI, lalu DITEBALKAN di sini (tidak diambil dengan
// `daisyuiThemes.lofi[...]`) supaya ke dua tema dijamin sama: `black`
// bawaannya 0 (siku) sementara `lofi` 0.25rem, dan kalau dibiarkan begitu
// gaya sudut akan ikut berubah setiap kali user toggle tema.
const radius = {
    '--rounded-box': '0.25rem',
    '--rounded-btn': '0.125rem',
    '--rounded-badge': '0.125rem',
    '--tab-radius': '0.125rem',
};

// Semantic color: gelap di tema terang, terang di tema gelap.
//
// Ini bukan sekadar selera. Komponen `alert` daisyUI mencampur warnanya dengan
// base-100, jadi pada permukaan gelap warna pastel terang + teks gelap = kontras
// pecah. Pola di bawah membuat warna semantic selalu terbaca sebagai TEKS di
// kedua mode: >= 600 (gelap) di terang, 400 (terang) di gelap. Nilai 400/600 ini
// juga sama dengan yang dipakai kode lama (`text-green-600 dark:text-green-400`),
// jadi hasil akhirnya tidak berbeda dari desain sebelumnya.
//
// Dipakai sebagai `text-{semantic}` untuk ikon/aksen dan `bg-{semantic}/10` untuk
// latar halus — bukan sebagai latar pekat. Lihat components/flash.blade.php.
const semanticLight = {
    info: '#0284c7', // sky-600
    success: '#16a34a', // green-600
    warning: '#d97706', // amber-600
    error: '#dc2626', // red-600
};

const semanticDark = {
    info: '#38bdf8', // sky-400
    success: '#4ade80', // green-400
    warning: '#fbbf24', // amber-400
    error: '#f87171', // red-400
};

// Terang. `lofi` = monokrom dengan tombol hampir hitam (#0D0D0D), paling dekat
// dengan karakter app ini.
const lofi = {
    ...daisyuiThemes.lofi,
    ...radius,
    ...semanticLight,
};

// Gelap. Tiga koreksi dari tema `black` bawaan:
//  1. base-100 > base-200 > base-300 (surface paling terang dulu). Bawaannya
//     #000 < #141414, jadi kartu terlihat "tenggelam". Susunan ini juga
//     mengikuti tema `dark` resmi daisyUI (#1d232a > #191e24 > #15191e).
//  2. Semantic color memakai palet terang (lihat catatan di atas), bukan neon
//     murni bawaan black (#008000 / #ffff00 / #ff0000).
//  3. Radius mengikuti tema terang supaya tidak berubah saat toggle.
const black = {
    ...daisyuiThemes.black,
    ...radius,
    'base-100': '#1c1c1c', // surface kartu  (sebelumnya #171717)
    'base-200': '#0d0d0d', // latar halaman (sebelumnya #0A0A0A)
    'base-300': '#2e2e2e', // border + hover (sebelumnya #333333)
    'base-content': '#e5e5e5', // teks utama    (sebelumnya #FAFAFA)
    // Primary di mode gelap dibalik jadi terang. Bawaan `black` bernilai
    // #373737 — abu gelap, sehingga `btn-primary` terlihat redup BERBEDA dari
    // tombol aksi utama yang lain. Sementara tema terang sudah benar:
    // `lofi` primary #0D0D0D dengan konten putih. Setelah dibalik di sini,
    // `btn-primary` memakai pola inversi yang sama dengan tab aktif, avatar, dan
    // tombol "Tambah" di sidebar, di kedua mode.
    //
    // `neutral` sengaja TIDAK diubah: ia dipakai badge sekunder (angka Sampah)
    // dan elemen non-aksi yang memang harus tetap redup.
    primary: '#e5e5e5',
    'primary-content': '#1c1c1c',
    ...semanticDark,
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
    // Tidak ada `theme.extend` warna/shadow lagi. Semua permukaan, border,
    // dan bayangan diambil dari komponen daisyUI (card, modal-box, btn, alert)
    // atau token temanya (base-100/200/300, base-content, primary, ...).
    // Menulis hex sendiri berarti keluar dari sistem dan hasilnya tidak
    // konsisten antar halaman.
    theme: {
        extend: {},
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

