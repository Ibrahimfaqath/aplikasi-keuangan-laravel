// ============================================================
// extractJson — parser toleran untuk output model reasoning.
//
// Model seperti deepseek-v4-flash sering membungkus JSON dalam:
//   - ```json ... ```
//   - teks pembuka/penutup ("Berikut hasilnya: {...} semoga membantu")
//   - whitespace / newline berlebih
//
// Kontrak: kembalikan objek { reply: string, transaction?: ... }
// atau null bila tidak ada JSON valid. TIDAK pernah throw.
// ============================================================

/**
 * @param {unknown} text output mentah dari model
 * @returns {{ reply: string, transaction?: unknown } | null}
 */
export function extractJson(text) {
    if (typeof text !== "string") return null;
    const trimmed = text.trim();
    if (trimmed === "") return null;

    const candidates = [];

    // 1. Fenced code block ```json ... ``` (paling umum dari reasoning model)
    const fenced = trimmed.match(/```(?:json)?\s*([\s\S]*?)```/i);
    if (fenced?.[1]) candidates.push(fenced[1].trim());

    // 2. Seluruh string siapa tahu sudah JSON murni
    candidates.push(trimmed);

    // 3. Substring rakus { ... } pertama-terakhir (fallback teks kotor)
    const greedy = trimmed.match(/\{[\s\S]*\}/)?.[0];
    if (greedy) candidates.push(greedy.trim());

    for (const candidate of candidates) {
        if (!candidate) continue;
        try {
            const obj = JSON.parse(candidate);
            if (obj && typeof obj === "object" && typeof obj.reply === "string") {
                return obj;
            }
        } catch {
            // coba kandidat berikutnya
        }
    }

    return null;
}
