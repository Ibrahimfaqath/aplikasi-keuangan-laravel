<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Guard statis untuk kelas bug "PHP bocor ke atribut HTML".
 *
 * Dua bug berbeda di dua halaman berbeda lolos 288 test fungsional,
 * karena keduanya hanya muncul saat Alpine mengurai atribut tersebut
 * di browser:
 *
 * - categories/index.blade.php: `:class="... || $errors->has('name')"`
 *   -> Blade hanya mengevaluasi PHP di dalam {{ }} / {!! !!} / @php,
 *      jadi teks itu masuk mentah ke parser Alpine: SyntaxError
 *      "Unexpected token '>'".
 * - transactions/trashed.blade.php: `:placeholder="\App\...\::CONST"`
 *      -> sama, "Invalid or unexpected token". Setelah dibungkus {{ }}
 *      masih salah: `x-bind:` mengevaluasi nilainya sebagai EKSPRESI JS,
 *      bukan string literal, jadi konstanta itu jadi identifier tak
 *      terdefinisi ("HAPUS is not defined").
 *
 * Uji fungsional tidak bisa menangkap ini — hanya browser yang bisa.
 * Guard ini memindai seluruh file blade, jadi kelas bug yang sama tidak
 * bisa muncul lagi diam-diam di halaman yang belum pernah dites manual.
 */
class BladeBindingTest extends TestCase
{
    /**
     * Variabel magic Alpine. Dimulai dengan `$` tapi BUKAN PHP — kalau tidak
     * dikecualikan, tiap `x-model="$event..."` akan dilaporkan palsu.
     */
    private const ALPINE_MAGIC = [
        'event', 'refs', 'dispatch', 'el', 'wire', 'root',
        'data', 'store', 'id', 'nextTick', 'watch',
    ];

    private function bladeFiles(): array
    {
        $files = [];

        foreach (glob(resource_path('views/**/*.blade.php')) ?: [] as $path) {
            $files[$path] = file_get_contents($path);
        }

        return $files;
    }

    /** Nama file + nomor baris dari offset byte. */
    private function locate(string $source, int $offset): string
    {
        return substr_count(substr($source, 0, $offset), "\n") + 1;
    }

    /**
     * Sensor tag komponen Blade (`<x-...>`) menjadi spasi, panjangnya
     * dipertahankan supaya nomor baris tidak bergeser.
     *
     * Ini wajib karena memindai atribut per-tag tidak akan berhasil: pola
     * `<[a-zA-Z][^>]*>` berhenti di karakter `>` pertama, dan `->` pada
     * `$errors->has('name')` mengandung `>` — tag jadi terpotong tepat di
     * tengah nilai atribut yang justru mau diperiksa. Bug ini sempat
     * membuat guard lewat dari salah satu insiden yang seharusnya ia
     * tangkap.
     */
    private function maskComponents(string $source): string
    {
        $pattern = '/<x-[a-zA-Z0-9:._-]+(?:\s+(?:"[^"]*"|\'[^\']*\'|[^>"\'])*)?\/?>/s';

        return preg_replace_callback($pattern, fn ($m) => str_repeat(' ', strlen($m[0])), $source) ?? $source;
    }

    /**
     * Setiap atribut Alpine binding yang ada di file, dengan nilai yang
     * sudah dibersihkan dari {{ }} / {!! !!} / @js / @json.
     *
     * @return list<array{attr: string, value: string, line: int, name: string}>
     */
    private function bindings(string $source, string $name): array
    {
        $found = [];
        $masked = $this->maskComponents($source);

        // Semua direktif Alpine yang punya nilai atribut, bukan hanya
        // x-bind:/x-on:. x-text, x-show, x-html, x-if, x-model, dan
        // seterusnya juga bisa jadi tempat PHP bocor ke parser JS.
        $pattern = '/(?:^|\s)(:[@\w:.\-]+|@[\w:.\-]+|x-[\w\-]+|v-[\w\-:.]+)="([^"]*)"/s';

        if (! preg_match_all($pattern, $masked, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $found;
        }

        foreach ($matches as $match) {
            // x-data="{ ... }" adalah object literal JS yang sering panjang dan
            // wajar memuat '$refs' atau backslash. Boleh lewat apa adanya.
            if (str_starts_with(trim($match[2][0]), '{')) {
                continue;
            }

            $value = $match[2][0];
            $stripped = preg_replace('/\{\{.*?\}\}|\{!!.*?!!\}|@js\(.*?\)|@json\(.*?\)/s', '', $value);

            if (! str_contains($stripped, '$') && ! preg_match('/\\\\?[A-Za-z_]\w*::[A-Z_]\w*/', $stripped)) {
                continue;
            }

            $found[] = [
                'attr' => $match[1][0],
                'value' => trim($value),
                'line' => $this->locate($source, $match[0][1]),
                'name' => $name,
            ];
        }

        return $found;
    }

    public function test_tidak_ada_php_mentah_di_binding_alpine(): void
    {
        $failures = [];
        $magic = implode('|', self::ALPINE_MAGIC);

        foreach ($this->bladeFiles() as $path => $source) {
            foreach ($this->bindings($source, basename($path)) as $b) {
                $stripped = preg_replace(
                    '/\{\{.*?\}\}|\{!!.*?!!\}|@js\(.*?\)|@json\(.*?\)/s',
                    '',
                    $b['value']
                );

                // Konstanta kelas yang belum dievaluasi Blade.
                if (preg_match('/(\\\\?[A-Za-z_]\w*(?:\\\\\w+)*)::[A-Z_]\w*/', $stripped)) {
                    $failures[] = sprintf(
                        '%s:%d  [%s] konstanta PHP tanpa {{ }} → %s',
                        $b['name'],
                        $b['line'],
                        $b['attr'],
                        $b['value']
                    );
                }

                // Variabel PHP, dengan magic Alpine dikecualikan.
                if (preg_match('/\$(?!\{)(?!('.$magic.')\b)[a-zA-Z_]/', $stripped)) {
                    $failures[] = sprintf(
                        '%s:%d  [%s] variabel PHP tanpa {{ }} → %s',
                        $b['name'],
                        $b['line'],
                        $b['attr'],
                        $b['value']
                    );
                }
            }
        }

        $this->assertSame([], $failures, "PHP mentah di binding Alpine:\n".implode("\n", $failures));
    }

    public function test_placeholder_tidak_pernah_dibuat_binding(): void
    {
        $failures = [];

        foreach ($this->bladeFiles() as $path => $source) {
            $masked = $this->maskComponents($source);

            if (! preg_match_all('/\s:placeholder="([^"]*)"/', $masked, $m, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($m[0] as [$hit, $offset]) {
                $failures[] = basename($path).':'.$this->locate($source, $offset).'  '.trim($hit);
            }
        }

        $this->assertSame([], $failures, "placeholder jangan pakai x-bind:\n".implode("\n", $failures));
    }

    public function test_hanya_ada_yang_punya_placeholder_kosong(): void
    {
        // kebalikan dari test di atas: pastikan guard-nya tidak jadi hampa —
        // view memang harus punya placeholder statis untuk dialog konfirmasi.
        $found = false;

        foreach ($this->bladeFiles() as $source) {
            if (str_contains($source, 'EMPTY_TRASH_CONFIRMATION')) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'expected: view Sampah masih memakai konstanta EMPTY_TRASH_CONFIRMATION');
    }
}
