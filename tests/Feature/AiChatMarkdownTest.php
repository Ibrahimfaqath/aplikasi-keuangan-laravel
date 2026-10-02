<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Markdown;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Reply asisten AI masih markdown mentah (`**tebal**`, `- butir`, `1. butir`).
 * Sebelum ada renderer, karakter itu bocor apa adanya ke gelembung chat.
 */
class AiChatMarkdownTest extends TestCase
{
    #[Test]
    public function teks_tebal_dijadi_bold(): void
    {
        $html = Markdown::render('**Ringkasan Transaksi:** bensin');

        $this->assertStringContainsString('<strong', $html);
        $this->assertStringContainsString('Ringkasan Transaksi:', $html);
        $this->assertStringNotContainsString('**', $html);
    }

    #[Test]
    public function butir_dash_jadi_daftar(): void
    {
        $html = Markdown::render("- Bensin 50.000\n- Kopi 38.000");

        $this->assertStringContainsString('<ul', $html);
        $this->assertSame(2, substr_count($html, '<li>'));
        $this->assertStringNotContainsString('- Bensin', $html);
    }

    #[Test]
    public function butir_bernomor_jadi_daftar_berurutan(): void
    {
        $html = Markdown::render("1. Pertama\n2. Kedua");

        $this->assertStringContainsString('<ol', $html);
        $this->assertSame(2, substr_count($html, '<li>'));
    }

    #[Test]
    public function enter_baru_tetap_terlihat(): void
    {
        $html = Markdown::render("baris satu\nbaris dua");

        $this->assertStringContainsString('<br', $html);
    }

    /**
     * AI kadang mengirim baris yang tak sengaja menjorok. Dengan
     * `whitespace-pre-wrap` di gelembung, spasi itu ikut tercetak dan paragraf
     * terlihat meleset ke kanan — persis yang terjadi sebelum test ini ada.
     */
    #[Test]
    public function spasi_di_awal_baris_dibuang(): void
    {
        $html = Markdown::render("   Oke, catat dulu ya!\n\n  **Ringkasan:**\n   - satu\n\t  - dua");

        $this->assertStringStartsWith('Oke, catat dulu ya!', $html);
        $this->assertStringContainsString('>Ringkasan:</strong>', $html);
        $this->assertStringContainsString('<li>satu</li>', $html);
        $this->assertStringContainsString('<li>dua</li>', $html);
        $this->assertStringNotContainsString('<li>  ', $html);
        $this->assertStringNotContainsString('<li>	', $html);
    }

    /**
     * Ini yang bikin renderer boleh dipakai tanpa DOMPurify: teks mentah dari AI
     * tidak boleh pernah jadi tag yang dieksekusi browser.
     *
     * Perhatikan: substring "onerror=" memang tetap muncul di hasil, karena ia
     * bagian dari teks yang sudah di-escape (`&lt;img ... onerror=...&gt;`).
     * Yang berbahaya justru `<img` sebagai tag nyata, makanya yang dicek adalah
     * tag-nya — bukan teksnya.
     */
    #[Test]
    public function html_dari_ai_berbahaya_selalu_escape(): void
    {
        $html = Markdown::render('<script>alert(1)</script> <img src=x onerror=alert(2)>');

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('&lt;img', $html);

        // Hanya tag milik renderer sendiri yang boleh muncul.
        preg_match_all('/<\s*([a-z0-9]+)/i', $html, $m);
        $this->assertSame([], array_diff(
            array_unique(array_map('strtolower', $m[1])),
            ['strong', 'em', 'code', 'li', 'ul', 'ol', 'br'],
        ), 'Ada tag asing yang lolos escaping: '.implode(', ', array_unique($m[1])));
    }

    #[Test]
    public function input_kosong_tidak_menghasilkan_html(): void
    {
        $this->assertSame('', Markdown::render(''));
        $this->assertSame('', Markdown::render(null));
        $this->assertSame('', Markdown::render('   '));
    }

    /**
     * Pesan yang sudah tersimpan di session dirender PHP, pesan yang baru datang
     * dari fetch dirender JS. Kalau dua versi melenceng, gelembung akan "berubah"
     * begitu halaman di-refresh — jadi keduanya harus sama persis.
     */
    #[Test]
    public function markdown_php_dan_js_selaras(): void
    {
        $samples = [
            '**Ringkasan Transaksi:**',
            "- Bensin 50.000\n- Kopi susu 38.000",
            "1. Pertama\n2. Kedua",
            "baris satu\nbaris dua",
            'italic *miring* dan kode `npm run dev`',
            '<script>alert("xss")</script> & teks & "kutip"',
            'paragraf 1\n\nparagraf 2 setelah baris kosong',
        ];

        $php = array_map(static fn (string $s): string => Markdown::render($s), $samples);
        $js = $this->renderWithNode($samples);

        foreach ($samples as $i => $sample) {
            $this->assertSame(
                $php[$i],
                $js[$i],
                "Hasil PHP dan JS berbeda untuk input: {$sample}"
            );
        }
    }

    /**
     * @param  list<string>  $samples
     * @return list<string>
     */
    private function renderWithNode(array $samples): array
    {
        $script = <<<'JS'
            import { renderAiMarkdown } from './resources/js/ai-markdown.js';
            const input = JSON.parse(process.argv[2]);
            process.stdout.write(JSON.stringify(input.map(renderAiMarkdown)));
            JS;

        $path = base_path('ai-markdown-check.mjs');
        file_put_contents($path, $script);

        try {
            $out = shell_exec(
                'cd '.escapeshellarg(base_path())
                .' && node --no-warnings '.escapeshellarg($path).' '
                .escapeshellarg(json_encode($samples)).' 2>&1'
            );
        } finally {
            @unlink($path);
        }

        $decoded = json_decode((string) $out, true);

        $this->assertIsArray($decoded, "Gagal menjalankan renderer JS: {$out}");

        return $decoded;
    }
}
