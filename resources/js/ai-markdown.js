/**
 * Cermin persis dari app/Support/Markdown.php.
 *
 * Reply asisten AI datang lewat fetch sebagai JSON mentah, jadi sisi klien
 * butuh renderer yang sama dengan sisi server. Kalau kedua versi ini melenceng,
 * gelembung chat akan terlihat berbeda setelah refresh — itu yang di cegah test
 * `test_markdown_php_dan_js_selaras`.
 *
 * Aturan keamanan sama: escape dulu, baru sisipkan tag milik sendiri. Jadi
 * tidak perlu DOMPurify untuk teks dari AI.
 */

const TAGS = {
    strong: 'font-semibold text-base-content',
    em: 'italic',
    code: 'font-mono text-xs bg-base-200 px-1 py-0.5 rounded-box',
    li: 'ml-4 list-disc',
    ul: 'my-1 space-y-0.5',
    ol: 'my-1 space-y-0.5 list-decimal ml-4',
};

/**
 * Sama persis dengan htmlspecialchars(ENT_QUOTES) di sisi PHP — termasuk
 * `&#039;` untuk tanda kutip tunggal, supaya hasil render server dan klien
 * bisa dibandingkan karakter demi karakter di test.
 *
 * Sengaja tanpa document.createElement: fungsi ini juga dipakai saat unit test
 * berjalan di node tanpa DOM.
 */
function escapeHtml(text) {
    return String(text ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderInline(text) {
    let html = text
        .replace(/\*\*(.+?)\*\*/gs, (_, inner) => `<strong class="${TAGS.strong}">${inner}</strong>`)
        .replace(/(?<![\*\w])\*([^*\n]+?)\*(?![\*\w])/gs, (_, inner) => `<em class="${TAGS.em}">${inner}</em>`)
        .replace(/`([^`\n]+?)`/gs, (_, inner) => `<code class="${TAGS.code}">${inner}</code>`);

    return html.replace(/\n/g, '<br>');
}

/**
 * Blok bisa campur. Reply AI umumnya umumnya seperti ini:
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
function renderBlock(block) {
    // Tiap baris di-trim, bukan cuma teks keseluruhan. AI sering mengirim baris
    // yang tak sengaja menjorok ("  Oke, catat dulu ya!"); karena gelembung
    // memakai whitespace-pre-wrap, spasi itu ikut tercetak.
    const lines = block
        .split('\n')
        .map((l) => l.trim())
        .filter((l) => l !== '');
    const bullet = /^\s*[-*]\s+/;
    const ordered = /^\s*\d+\.\s+/;

    const out = [];
    let i = 0;

    while (i < lines.length) {
        if (bullet.test(lines[i])) {
            const items = [];
            while (i < lines.length && bullet.test(lines[i])) {
                items.push(`<li>${renderInline(lines[i].replace(bullet, ''))}</li>`);
                i++;
            }
            out.push(`<ul class="${TAGS.ul}">${items.join('')}</ul>`);
            continue;
        }

        if (ordered.test(lines[i])) {
            const items = [];
            while (i < lines.length && ordered.test(lines[i])) {
                items.push(`<li>${renderInline(lines[i].replace(ordered, ''))}</li>`);
                i++;
            }
            out.push(`<ol class="${TAGS.ol}">${items.join('')}</ol>`);
            continue;
        }

        const paragraph = [];
        while (i < lines.length && !bullet.test(lines[i]) && !ordered.test(lines[i])) {
            paragraph.push(lines[i]);
            i++;
        }
        out.push(renderInline(paragraph.join('\n')));
    }

    return out.join('');
}

/** @param {string} text @returns {string} HTML aman untuk innerHTML */
export function renderAiMarkdown(text) {
    const trimmed = (text ?? '').trim();

    if (trimmed === '') return '';

    const safe = escapeHtml(trimmed);

    return safe
        .split(/\n{2,}/)
        .map(renderBlock)
        .filter(Boolean)
        .join('<div class="h-2"></div>');
}