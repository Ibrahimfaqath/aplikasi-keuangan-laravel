<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Render pesan asisten AI yang masih berupa markdown.
 *
 * AI dilatih replying dengan `**tebal**`, `- butir`, dan `1. butir`. Sebelum
 * ini teks itu dibuang ke dalam `<p>` polos, jadi user melihat karakter mentah
 * seperti `**Ringkasan Transaksi:**` di gelembung chat.
 *
 * Aturan main: escape DULU, baru sisipkan tag milik kita. Itu yang bikin fungsi
 * ini aman dipakai untuk output AI tanpa perlu DOMPurify.
 *
 * Versi JavaScript-nya ada di resources/js/ai-markdown.js dan sengaja
 * meniru baris demi baris. Kalau salah satu diubah, keduanya harus ikut —
 * test `test_markdown_php_dan_js_selaras` menjaga supaya tidak melenceng.
 */
final class Markdown
{
    /** Tag yang boleh keluar dari teks AI. Daftar ini jadi allowlist. */
    private const TAGS = [
        'strong' => 'font-semibold text-base-content',
        'em' => 'italic',
        'code' => 'font-mono text-xs bg-base-200 px-1 py-0.5 rounded-box',
        'li' => 'ml-4 list-disc',
        'ul' => 'my-1 space-y-0.5',
        'ol' => 'my-1 space-y-0.5 list-decimal ml-4',
        'br' => '',
    ];

    /**
     * Escape → inline → blok (baris demi baris).
     */
    public static function render(?string $text): string
    {
        $text = trim((string) $text);

        if ($text === '') {
            return '';
        }

        // 1. Escape dulu. Setelah titik ini tidak ada karakter '&', '<', '>',
        //    '"', atau "'" yang berasal dari user/AI.
        $safe = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 2. Pisahkan blok: baris kosong jadi pemisah paragraf/daftar.
        $blocks = preg_split('/\n{2,}/', $safe) ?: [$safe];
        $html = [];

        foreach ($blocks as $block) {
            $html[] = self::block($block);
        }

        return implode('<div class="h-2"></div>', array_filter($html));
    }

    /**
     * Satu blok. Yang penting: blok bisa campur, karena reply AI umumnya
     * begins like ini —
     *
     *     **Ringkasan Transaksi:**
     *     - Jenis: Pengeluaran
     *     - Jumlah: Rp 77.000
     *
     * Versi lama menolak begitu saja: satu baris non-butir bikin SELURUH blok
     * diperlakukan sebagai paragraf, jadi butirnya tampil dengan "- " mentah.
     * Sekarang baris dihitung per rentang: yang berurutan jadi satu daftar,
     * sisanya jadi paragraf.
     */
    private static function block(string $block): string
    {
        // Tiap baris di-trim, bukan cuma teks keseluruhan. AI sering mengirim
        // baris yang tak sengaja menjorok ("  Oke, catat dulu ya!"); karena
        // gelembung pakai whitespace-pre-wrap, spasi itu ikut tercetak dan
        // paragraf terlihat meleset ke kanan.
        $lines = array_values(array_filter(
            array_map(
                static fn (string $l): string => trim($l),
                explode("\n", $block),
            ),
            static fn (string $l): bool => $l !== '',
        ));

        $html = [];
        $i = 0;
        $total = count($lines);

        $bullet = '/^\s*[-*]\s+/';
        $ordered = '/^\s*\d+\.\s+/';

        while ($i < $total) {
            // Daftar butir: kumpulkan selama baris berikutnya masih butir.
            if (preg_match($bullet, $lines[$i]) === 1) {
                $items = [];

                while ($i < $total && preg_match($bullet, $lines[$i]) === 1) {
                    $items[] = '<li>'.self::inline(self::stripMarker($lines[$i], $bullet)).'</li>';
                    $i++;
                }

                $html[] = '<ul class="'.self::TAGS['ul'].'">'.implode('', $items).'</ul>';

                continue;
            }

            // Daftar berurutan.
            if (preg_match($ordered, $lines[$i]) === 1) {
                $items = [];

                while ($i < $total && preg_match($ordered, $lines[$i]) === 1) {
                    $items[] = '<li>'.self::inline(self::stripMarker($lines[$i], $ordered)).'</li>';
                    $i++;
                }

                $html[] = '<ol class="'.self::TAGS['ol'].'">'.implode('', $items).'</ol>';

                continue;
            }

            // Paragraf: semua baris sampai butir berikutnya.
            $paragraph = [];

            while ($i < $total
                && preg_match($bullet, $lines[$i]) !== 1
                && preg_match($ordered, $lines[$i]) !== 1) {
                $paragraph[] = $lines[$i];
                $i++;
            }

            $html[] = self::inline(implode("\n", $paragraph));
        }

        return implode('', $html);
    }

    /**
     * Inline: `**tebal**`, `*miring*`, `kode`, lalu baris baru → <br>.
     */
    private static function inline(string $text): string
    {
        $html = preg_replace(
            '/\*\*(.+?)\*\*/s',
            '<strong class="'.self::TAGS['strong'].'">$1</strong>',
            $text,
        ) ?? $text;

        $html = preg_replace(
            '/(?<![\*\w])\*([^*\n]+?)\*(?![\*\w])/s',
            '<em class="'.self::TAGS['em'].'">$1</em>',
            $html,
        ) ?? $html;

        $html = preg_replace(
            '/`([^`\n]+?)`/s',
            '<code class="'.self::TAGS['code'].'">$1</code>',
            $html,
        ) ?? $html;

        // str_replace, bukan nl2br(): nl2br memunculkan `<br />` (dengan slash)
        // sedangkan versi JS menghasilkan `<br>`. Selisih satu karakter itu
        // sudah cukup buat test keselarasan gagal.
        return str_replace("\n", '<br>', $html);
    }

    /**
     * Buang penanda daftar ("- " atau "1. ") dari satu baris.
     *
     * Tanpa ini bullet-nya ikut ter-render dan user lihat "- Bensin 50.000"
     * di dalam <li>.
     */
    private static function stripMarker(string $line, string $pattern): string
    {
        return (string) preg_replace($pattern, '', $line);
    }
}
