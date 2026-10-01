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
    private const TERLARANG = [
        'dark:bg-[#' => 'warna latar gelap ditulis sebagai hex — pakai token base-100 / base-200 / base-300',
        'dark:text-[#' => 'warna teks gelap ditulis sebagai hex — pakai token base-content',
        'dark:border-[#' => 'warna border gelap ditulis sebagai hex — pakai token base-300',
        'dark:divide-[#' => 'warna pemisah gelap ditulis sebagai hex — pakai token base-300',
        'dark:ring-[#' => 'warna ring gelap ditulis sebagai hex — pakai token base-content',
        'dark:placeholder-[#' => 'warna placeholder gelap ditulis sebagai hex — pakai token base-content',
        'bg-[#' => 'warna latar terang ditulis sebagai hex — pakai token base-100 / base-200',
        'text-[#' => 'warna teks terang ditulis sebagai hex — pakai token base-content',
        'border-[#' => 'warna border terang ditulis sebagai hex — pakai token base-300',
    ];

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

    private function bladeFiles(): array
    {
        $files = [];

        foreach (glob(resource_path('views/**/*.blade.php')) ?: [] as $path) {
            $files[$path] = file_get_contents($path);
        }

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

            foreach (self::TERLARANG as $pola => $alasan) {
                $offset = 0;

                while (($offset = strpos($source, $pola, $offset)) !== false) {
                    // `theme_color` di <meta> bukan gaya dan memang boleh hex.
                    $baris = $this->locate($source, $offset);
                    $konteks = explode("\n", $source)[$baris - 1] ?? '';

                    if (! str_contains($konteks, '<meta')) {
                        $bermasalah[] = sprintf(
                            '%s:%d  %s  (%s)',
                            $this->relative($path),
                            $baris,
                            $pola,
                            $alasan
                        );
                    }

                    $offset += strlen($pola);
                }
            }
        }

        $this->assertSame(
            [],
            $bermasalah,
            "Ada warna hex yang ditulis tangan. Pakai token tema daisyUI:\n - ".implode("\n - ", $bermasalah)
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
