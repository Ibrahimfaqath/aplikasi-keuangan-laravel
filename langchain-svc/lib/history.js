// ============================================================
// history — normalisasi riwayat chat untuk memory LangChain.
//
// Kontrak input (dari Laravel session maupun POST body):
//   [{ role: "user"|"assistant", content: "..." }]
//   Laravel mengirim {role:"user",text:"..."}? Kami terima dua alias:
//   content ATAU text.
//
// Aturan profesional:
//   - Maks 20 pesan terakhir (10 pertukaran) agar tidak jebol token.
//   - Tiap pesan dipotong max 2000 karakter (selaras AiController).
//   - Role selain user/assistant dibuang (anti prompt-injection via role system).
//   - TIDAK pernah throw — kembalikan [] bila input sampah.
// ============================================================

export const MAX_HISTORY_ITEMS = 20;
export const MAX_HISTORY_CHARS = 2000;

/**
 * @param {unknown} raw
 * @returns {Array<{role:"user"|"assistant", content:string}>}
 */
export function normalizeHistory(raw) {
    if (!Array.isArray(raw)) return [];
    const clean = [];
    for (const item of raw) {
        if (!item || typeof item !== "object") continue;
        const role = item.role;
        if (role !== "user" && role !== "assistant") continue;
        const content = item.content ?? item.text ?? "";
        if (typeof content !== "string") continue;
        const trimmed = content.trim();
        if (trimmed === "") continue;
        clean.push({ role, content: trimmed.slice(0, MAX_HISTORY_CHARS) });
    }
    // Ambil ekor saja (percakapan terbaru) bila kepanjangan.
    return clean.slice(-MAX_HISTORY_ITEMS);
}
