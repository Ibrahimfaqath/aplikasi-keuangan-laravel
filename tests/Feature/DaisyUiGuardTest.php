<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guard statis untuk memastikan migrasi ke daisyUI tidak bocor kembali.
 *
 * Latar: setelah seluruh kelas warna dan komponen daisyUI dipasang (lihat
 * resources/css/app.css dan tailwind.config.js), pola yang DILARANG ini
 * beberapa kali muncul kembali tanpa disadari:
 *
 * - Warna ditulis tangan lagi sebagai pasangan `bg-white dark:bg-[#171717]`.
 *   Totalnya ratusan pasang, dan setiap pasang hanya benar untuk satu tema —
 *   begitu tema berubah, separuhnya jadi tidak cocok.
 * - Kelas daisyUI dirangkai dari variabel Blade (`class="btn btn-{{ $v }}"`).
 *   Tailwind memindai file .blade.php sebagai TEKS, bukan mengeksekusi Blade,
 *   jadi kelas yang dirangkai tidak pernah terlihat dan TER-PURGE. Elemennya
 *   lalu kehilangan seluruh styling tanpa error apa pun. Ini sudah terjadi
 *   di components/notification-bell.blade.php (fungsi iconClass()).
 * - `menu-active` dan `modal-sm/md/lg/xl` dipakai padahal itu fitur daisyUI 5
 *   atau tidak pernah ada; di 4.12.24 keduanya tidak menghasilkan CSS sama
 *   sekali, jadi elemennya tampil tanpa gaya.
 *
 * Uji fungsional tidak bisa menangkap semua ini — perlu build CSS. Guard ini
 * memindai sumber, jadi kelas bug yang sama tidak bisa masuk lagi diam-diam.
 */
class DaisyUiGuardTest extends TestCase
{
    /**
     * Warna yang AKTIF untuk suatu elemen, ditulis sebagai pasangan
     * "utility terang + dark:utility hex". Semua permukaan, border, dan teks
     * harus lewat token tema (base-100/200/300, base-content, primary, ...)
     * yang otomatis benar di kedua mode.
     */
    /**
     * Warna hex yang ditulis tangan di utilitas Tailwind mana pun.
     *
     * Dulu daftar ini hanya memuat `bg-[#`, `text-[#`, `border-[#` dan
     * handful varian `dark:`. Akibatnya `divide-[#333333]` di
     * transactions/index lolos padahal persis pelanggaran yang dimaksud --
     * dan tidak ada satu pun punyek untuk `ring-[#`, `outline-[#`,
     * `shadow-[#`, `from-[#`, `placeholder-[#`, dll.
     *
     * Sekarang satu regex menutup seluruh prefiks utilitas warna, dengan atau
     * tanpa `dark:`.
     */
    private const PENOLAKAN_HEX = '/\b(?:dark:)?(?:bg|text|border|divide|ring|outline|shadow|from|via|to|decoration|accent|caret|fill|stroke|placeholder)-\[#[0-9a-fA-F]{3,8}\]/';

    /** Utilitas mati yang warnanya sudah diambil alih tema. */
    private const UTILITAS_MATI = [
        'animate-shimmer' => 'sudah diganti kelas `skeleton` daisyUI',
        'animate-bounce' => 'sudah diganti kelas `loading loading-dots` daisyUI',
    ];

    /**
     * Palet netral milik Tailwind. Tetap boleh dipakai untuk warna DOMAIN
     * (mis. warna kategori dari CategoryStyle), tapi TIDAK untuk permukaan,
     * border, atau teks — semuanya milik tema. Semua pemakaian saat ini
     * sudah dipindahkan ke token, jadi kemunculannya lagi berarti regresi.
     */
    private const NETRAL_TAILWIND = '/\b(?:bg|text|border|ring|divide|placeholder|from|to|via|hover:bg|hover:text|hover:border|focus:border|focus:ring|focus:bg)-neutral-\d{2,3}\b/';

    /**
     * `neutral-950` diizinkan: itu scrim overlay yang HARUS gelap di kedua
     * mode. Memakai base-content akan terbalik di tema gelap.
     */
    private const NETRAL_DI_KECUALIKAN = ['neutral-950'];

    /** Kelas daisyUI yang TIDAK ada di versi yang dipakai (4.12.24). */
    private const KELAS_TIDAK_ADA = [
        'menu-active' => 'di daisyUI v4 penanda menu aktif adalah `active`, bukan `menu-active` (itu fitur v5)',
        'modal-sm' => 'daisyUI v4 tidak punya modifier ukuran modal; pakai `sm:max-w-*` di atas modal-box',
        'modal-md' => 'daisyUI v4 tidak punya modifier ukuran modal; pakai `sm:max-w-*` di atas modal-box',
        'modal-lg' => 'daisyUI v4 tidak punya modifier ukuran modal; pakai `sm:max-w-*` di atas modal-box',
        'modal-xl' => 'daisyUI v4 tidak punya modifier ukuran modal; pakai `sm:max-w-*` di atas modal-box',
    ];

    /** Palet Tailwind: warna tetap yang tidak mengikuti tema sama sekali. */
    private const PALET_TAILWIND = '/\b(?:bg|text|border|ring|fill|stroke|from|to|via)-(?:red|green|emerald|blue|yellow|amber|orange|purple|pink|indigo|cyan|teal|sky|lime|violet|rose)-[0-9]{2,3}\b/';

    private function bladeFiles(): array
    {
        $files = [];

        // PENTING: pakai RecursiveDirectoryIterator, bukan glob('**/*.blade.php').
        // `**` di glob() PHP BUKAN wildcard rekursif -- ia cuma berarti satu
        // tingkat folder. Akibatnya 9 file di views/transactions/partials/ dan
        // friends TIDAK PERNAH diperiksa sama sekali, jadi semua guard di bawah
        // bisa lolos padahal ada pelanggaran di file-file itu.
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[$file->getPathname()] = file_get_contents($file->getPathname());
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * Sensor komentar jadi spasi, panjangnya dipertahankan supaya nomor
     * baris tidak bergeser.
     *
     * Tanpa ini guard ini melaporkan dirinya sendiri: baik flash.blade.php
     * maupun sidebar.blade.php MENJELASKAN di komentar kenapa kelas daisyUI
     * tidak boleh dirangkai dan kenapa `menu-active` tidak dipakai — dan
     * penjelasan itu sendiri mengandung pola yang dicari.
     *
     * Komentar baris `//` hanya disensor kalau diawali whitespace, supaya
     * `https://` di dalam atribut tidak ikut terhapus.
     */
    private function maskComments(string $source): string
    {
        $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source, -1, $n1);
        $source = preg_replace('/\{\{!.*?\}\}/s', '', $source, -1, $n2);
        $source = preg_replace('#/\*.*?\*/#s', '', $source, -1, $n3);
        $source = preg_replace('/^[ \t]*\/\/.*$/m', '', $source, -1, $n4);

        return $source;
    }

    /** Nama file relatif + nomor baris, supaya pesan error bisa langsung dipakai. */
    private function locate(string $source, int $offset): string
    {
        return substr_count(substr($source, 0, $offset), "\n") + 1;
    }

    private function relative(string $path): string
    {
        return str_replace(resource_path('views/'), '', $path);
    }

    public function test_tidak_ada_warna_hex_ditulis_tangan_di_blade(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);
            $baris_all = explode("\n", $source);

            if (! preg_match_all(self::PENOLAKAN_HEX, $source, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($m[0] as [$kelas, $offset]) {
                $baris = $this->locate($source, $offset);
                $konteks = $baris_all[$baris - 1] ?? '';

                // `theme-color` di dalam <meta> bukan gaya dan memang hex.
                if (str_contains($konteks, '<meta')) {
                    continue;
                }

                $bermasalah[] = sprintf(
                    '%s:%d  %s  -- pakai token daisyUI, bukan hex',
                    $this->relative($path),
                    $baris,
                    trim($kelas)
                );
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Warna hex ditulis tangan di utilitas Tailwind:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_tidak_ada_palet_netral_tailwind_untuk_permukaan(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            if (preg_match_all(self::NETRAL_TAILWIND, $source, $cocok, PREG_OFFSET_CAPTURE)) {
                foreach ($cocok[0] as [$kelas, $offset]) {
                    $shade = substr($kelas, strrpos($kelas, 'neutral-') + 8);

                    if (in_array('neutral-'.$shade, self::NETRAL_DI_KECUALIKAN, true)) {
                        continue;
                    }

                    $bermasalah[] = sprintf(
                        '%s:%d  %s',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        $kelas
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Permukaan/border/teks masih memakai palet netral Tailwind, bukan token tema:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_tidak_ada_kelas_daisyui_yang_dirangkai_dari_variabel_blade(): void
    {
        // `alert-{{ $v }}` dan sejenisnya. Tailwind memindai .blade.php sebagai
        // teks, jadi kelas yang dirangkai tidak pernah terlihat dan ter-purge.
        $pola = '/\b(?:btn|card|alert|badge|input|select|textarea|checkbox|radio|toggle|tab|table|stats|stat|steps|navbar|menu|tooltip|loading|skeleton|modal|toast|progress|join|avatar|indicator|link|file-input|range|chat|timeline|breadcrumb)[a-z-]*-\{\{/i';

        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            if (preg_match_all($pola, $source, $cocok, PREG_OFFSET_CAPTURE)) {
                foreach ($cocok[0] as [$teks, $offset]) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        $teks
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            'Kelas daisyUI dirangkai dari variabel Blade dan akan ter-purge. '
            ."Tulis nama kelas utuh sebagai literal:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_tidak_ada_kelas_daisyui_yang_tidak_ada_di_versi_terpasang(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            foreach (self::KELAS_TIDAK_ADA as $kelas => $alasan) {
                $offset = 0;

                while (($offset = strpos($source, $kelas, $offset)) !== false) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s  (%s)',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        $kelas,
                        $alasan
                    );

                    $offset += strlen($kelas);
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Kelas daisyUI yang tidak ada di versi terpasang:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_tidak_ada_utilitas_yang_sudah_diganti(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            foreach (self::UTILITAS_MATI as $kelas => $alasan) {
                $offset = 0;

                while (($offset = strpos($source, $kelas, $offset)) !== false) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s  (%s)',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        $kelas,
                        $alasan
                    );

                    $offset += strlen($kelas);
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Utilitas yang sudah diganti komponen daisyUI masih dipakai:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_segmented_control_wajib_pakai_tabs_dan_grid_cols(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            // `tabs-boxed` daisyUI memakai display:grid dengan kolom auto, jadi
            // tiap tab ikut lebar teksnya: "Semua" jadi jauh lebih sempit dari
            // "Pengeluaran". Itu yang bikin segmented control terlihat murdering.
            // Fix: tambahkan grid-cols-N supaya tiap segmen sama lebar.
            if (preg_match_all('/class="([^"]*\\btabs-boxed\\b[^"]*)"/', $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[1] as [$kelas, $offset]) {
                    if (! preg_match('/\\bgrid-cols-\\d/', $kelas)) {
                        $bermasalah[] = sprintf(
                            '%s:%d  tabs-boxed tanpa grid-cols-N -> segmen tidak sama lebar',
                            $this->relative($path),
                            $this->locate($source, $offset)
                        );
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Segmented control tanpa grid-cols-N (tiap segmen jadi lebar berbeda):\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_tidak_ada_segmented_control_tulis_tangan(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            // Segmented control buatan sendiri = <button> berbentuk pil. Dua
            // gejalanya: <button> dibungkus <div class="badge"> (badge cuma
            // untuk label, bukan wadah tombol --sehingga tinggi terkunci 1rem dan
            // tombolnya meluber), dan tombol pil bergaya rounded-full.
            $pola = [
                '/<div class="badge[^"]*">(?:(?!<\\/div>).)*?<button/s' => 'tombol di dalam .badge',
                '/\\bpx-3\\.5 py-1\\.5 rounded-full\\b/' => 'pola segmented control lama (px-3.5 py-1.5 rounded-full)',
            ];

            foreach ($pola as $regex => $alasan) {
                if (preg_match($regex, $source, $m, PREG_OFFSET_CAPTURE)) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s',
                        $this->relative($path),
                        $this->locate($source, $m[0][1]),
                        $alasan
                    );
                }
            }

            // Tombol berbentuk pil (rounded-full) itu sah kalau sudah pakai
            // komponen btn daisyUI -- misalnya chip nominal cepat di /budgets.
            // Yang dilarang adalah <button> rounded-full TANPA kelas btn:
            // itu segmented control buatan sendiri yang tidak ikut terkunci
            // tinggi daisyUI, sehingga never aligns with tetangganya.
            if (preg_match_all('/<button[^>]*class="([^"]*)"[^>]*>/s', $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[1] as [$kelas, $offset]) {
                    if (str_contains($kelas, 'rounded-full') && ! preg_match('/\\bbtn\\b/', $kelas)) {
                        $bermasalah[] = sprintf(
                            '%s:%d  <button> rounded-full tanpa kelas btn',
                            $this->relative($path),
                            $this->locate($source, $offset)
                        );
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Segmented control tulis-tangan masih ada -- pakai tabs tabs-boxed + grid-cols-N:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_menu_dropdown_wajib_satu_kolom(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            // daisyUI `.menu` memakai `flex-flow: column wrap` -- item mengisi ke
            // bawah lalu MEMBUNGKUS ke kolom baru di kanan. Pada daftar dropdown
            // yang panjang (15 kategori) dengan max-height, daftar jadi terlihat
            // TERBELAH DUA: 13 item di kiri, 2 di kanan.
            //
            // `.dropdown-menu` yang memaksa `column nowrap`. Tanpa itu, dropdown
            // kategori tampil sebagai dua bagian.
            if (preg_match_all('/<ul(?![^>]*dropdown-menu)[^>]*class="[^"]*\\bdropdown-content\\b[^"]*"[^>]*>/s', $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$tag, $offset]) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s -- tambah kelas dropdown-menu (satu kolom, bukan column wrap)',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        trim(preg_replace('/\s+/', ' ', $tag))
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Menu dropdown tanpa `dropdown-menu` -- akan terbelah jadi dua kolom:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_radius_pakai_token_tema_bukan_nilai_tertulis(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);
            $baris_all = explode("\n", $source);

            // Tema hanya menyedikan dua radius: --rounded-box (.25rem) dan
            // --rounded-btn (.125rem), keduanya overridable lewat
            // tailwind.config.js. Kelas tetap seperti `rounded-2xl` (1rem)
            // mengabaikan tema sepenuhnya -- itulah kenapa kartu di dashboard
            // pernah bulat 16px padahal tokennya 4px.
            //
            // Pengecualian: `btn` dan `badge` sudah punya radius sendiri dari
            // daisyUI, jadi kelas di baris itu tidak boleh ikut ditulis ulang.
            if (preg_match_all('/\brounded-(?:2xl|xl|lg|md)\b/', $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$kelas, $offset]) {
                    $baris = $this->locate($source, $offset);
                    $konteks = $baris_all[$baris - 1] ?? '';

                    if (preg_match('/\b(btn|badge)\b/', $konteks)) {
                        continue;
                    }

                    $bermasalah[] = sprintf(
                        '%s:%d  %s -- pakai rounded-box / rounded-btn',
                        $this->relative($path),
                        $baris,
                        $kelas
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Radius ditulis langsung, jadi tidak ikut berubah saat token tema diganti:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_tidak_ada_select_native_pakai_dropdown_daisyui(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            // <select> dirender oleh sistem operasi: yang muncul saat diklik
            // adalah daftar BERWARNA PUTIH milik OS, satu-satunya bidang terang
            // yang tidak bisa mengikuti tema. Di aplikasi yang seluruhnya hitam
            // pekat, itu generasi yang paling mencolok.
            //
            // Pengganti: <x-select-dropdown> (dropdown + menu daisyUI).
            if (preg_match_all('/<select\b/', $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$tag, $offset]) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s -- pakai <x-select-dropdown> (dropdown + menu daisyUI)',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        $tag
                    );
                }
            }
        }

        // Pemakai komponennya harus benar-benar ada, kalau tidak guard ini
        // hanyaaturan tanpa recourse.
        $this->assertFileExists(
            resource_path('views/components/select-dropdown.blade.php'),
            'Komponen <x-select-dropdown> tidak ada -- tidak ada pengganti <select>.'
        );

        $this->assertSame(
            [],
            $bermasalah,
            "Masih ada <select> native (popup-nya putih, tidak bisa di-tema):\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_tidak_ada_bidang_abu_dan_teksnya_terbaca(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);
            $baris_all = explode("\n", $source);

            // (1) base-200 (#141414) DILARANG sebagai isian bidang. Dulu base-200 dipakai
            //    lahardipakai sebagai latar halaman -- halaman jadi berabu dan
            //     di layar OLED semua tampak seperti glow. Pemisahan antar bagian
            //     sekarang dipercayakan ke garis rambut border-base-300.
            if (preg_match_all('/\bbg-base-200(?:\/[0-9]+)?\b/', $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$kelas, $offset]) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s -- latar/latar panel harus base-100 (hitam) + garis border-base-300',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        trim($kelas)
                    );
                }
            }

            // (2) Teks dengan opasitas rendah tidak terbaca di atas hitam.
            //     base-content #d6d6d6 @40% = #555555 -> hanya 2.8:1, jauh di
            //     bawah ambang WCAG AA 4.5:1. Hierarki dibedakan lewat ukuran &
            //     bobot font, bukan denganTechnically redupkan teks.
            if (preg_match_all('/\b(?:text|placeholder:text)-base-content\/(?:20|30|40|50)\b/', $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$kelas, $offset]) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s -- minimal /60 (2.8:1 itu tidak terbaca di atas hitam)',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        trim($kelas)
                    );
                }
            }
        }

        // (3) Latar halaman harus base-100.
        $css = file_get_contents(base_path('resources/css/app.css'));

        $this->assertDoesNotMatchRegularExpression(
            '/@apply[^;]*\bbg-base-200\b/',
            $css,
            'Latar halaman tidak boleh base-200 (#141414) -- itu sumber abu-abu yang bikin halaman terasa tidak nyaman. Pakai base-100.'
        );

        $this->assertMatchesRegularExpression(
            '/@apply\s+bg-base-100\b/',
            $css,
            'Latar halaman harus explicit base-100 (hitam penuh).'
        );

        $this->assertSame(
            [],
            $bermasalah,
            "Ada bidang abu atau teks redup:\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_setiap_tabel_memakai_kelas_table_daisyui(): void
    {
        $bermasalah = [];

        foreach ($this->bladeFiles() as $path => $raw) {
            // pdf.blade.php dikecualikan: halamannya dirender DOMPDF yang tidak memuat
            // Tailwind sama sekali, jadi tabelnya digaya oleh <style> sendiri
            // (header-table, data-table, dst) -- bukan oleh daisyUI.
            if (str_ends_with($path, 'transactions/pdf.blade.php')) {
                continue;
            }

            $source = $this->maskComments($raw);

            // Tangkap semua tag <table>, lalu cek sendiri apakah class-nya
            // memuat `table` daisyUI. (Lookahead untuk ini tidak bisa dipakai:
            // `\btable\b` akan ikut cocok pada `table-zebra`.)
            if (preg_match_all('/<table\b([^>]*)>/s', $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[1] as [$atribut, $offset]) {
                    $class = preg_match('/\bclass="([^"]*)"/', $atribut, $c) ? $c[1] : '';

                    if (preg_match('/(?:^|\s)table(?:\s|$)/', $class)) {
                        continue;
                    }

                    $bermasalah[] = sprintf(
                        '%s:%d  %s -- pakai class="table" daisyUI',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        trim(preg_replace('/\s+/', ' ', '<table'.$atribut.'>'))
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Tabel tanpa kelas daisyUI `table` (styling lama seperti border-collapse):\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_warna_semantik_pakai_token_bukan_palet_tailwind(): void
    {
        $bermasalah = [];

        // Peta warna KATEGORI memang disengaja bervariasi -- itu identitas
        // kategori, bukan warna semantik, dan tiap warnanya punya pasangan
        // `dark:`. Jadi blok peta itu dikecualikan dari aturan ini.
        $petaKategori = '/\$pillThemes\s*=\s*\[.*?\];/s';

        foreach ($this->bladeFiles() as $path => $raw) {
            $source = $this->maskComments($raw);

            // Disamarkan dengan spasi agar panjang & offset tetap sama supaya
            // nomor baris di pesan error akurat.
            $source = preg_replace_callback($petaKategori, static fn (array $m): string => str_repeat(' ', strlen($m[0])), $source);

            if (preg_match_all(self::PALET_TAILWIND, $source, $m, PREG_OFFSET_CAPTURE)) {
                foreach ($m[0] as [$kelas, $offset]) {
                    $bermasalah[] = sprintf(
                        '%s:%d  %s -- pakai token daisyUI (mis. text-error, bg-success/10)',
                        $this->relative($path),
                        $this->locate($source, $offset),
                        $kelas
                    );
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Warna semantik masih memakai palet Tailwind (tidak ikut berubah saat tema diganti):\n - ".implode("\n - ", $bermasalah)
        );
    }

    public function test_setiap_tema_daisyui_terpasang_ada_di_konfigurasi(): void
    {
        $konfigurasi = file_get_contents(base_path('tailwind.config.js'));

        $this->assertStringContainsString('daisyui', $konfigurasi, 'Plugin daisyUI belum terdaftar.');
        $this->assertStringContainsString('themes: [{ lofi }, { black }]', $konfigurasi, 'Daftar tema berubah — sesuaikan juga theme-boot partial dan applyTheme() di app.js.');

        // base-100/200/300/base-content tema gelap harus MELETAKKAN nilai asli
        // daisyUI (tidak di-override). Kalau ada override hex di sini, mode
        // gelap akan terasa kurang pekat daripada tema `black` daisyUI — itu
        // yang pernah terjadi: base-100 dipaksa #1c1c1c sehingga sidebar dan
        // setiap kartu ter-render abu terang, bukan hitam pekat.
        foreach (["'base-100'", "'base-200'", "'base-300'", "'base-content'"] as $token) {
            // Menangkap pola  'base-100': '#1c1c1c'  — token override hex.
            $pola = '/'.preg_quote($token, '/').":\\s*'#/";

            $this->assertDoesNotMatchRegularExpression(
                $pola,
                $konfigurasi,
                $token.' tema gelap di-override. Pakai nilai asli daisyUI '
                .'supaya mode gelap benar-benar pekat.'
            );
        }

        // Nama tema harus sama di ketiga tempat yang merujuknya, kalau tidak
        // komponen daisyUI diam-diam memakai tema bawaan yang lain.
        $boot = file_get_contents(resource_path('views/partials/theme-boot.blade.php'));
        $app = file_get_contents(resource_path('js/app.js'));

        foreach (['lofi', 'black'] as $tema) {
            $this->assertStringContainsString(
                "'{$tema}'",
                $boot,
                "Tema '{$tema}' tidak diset di partial theme-boot, jadi komponen daisyUI akan pakai tema bawaan."
            );
            $this->assertStringContainsString(
                "'{$tema}'",
                $app,
                "Tema '{$tema}' tidak diset di app.js, jadi toggle tema tidak akan mengubah komponen daisyUI."
            );
        }
    }
}
