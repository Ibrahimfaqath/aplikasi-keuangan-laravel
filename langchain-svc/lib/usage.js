// ============================================================
// usage — ekstrak token usage dari respons LangChain/OpenAI.
//
// Kenapa perlu: tanpa ini kita buta biaya & performa.
// KiosAPI OpenAI-compatible bisa mengembalikan usage dalam 3 bentuk:
//   1. res.usage_metadata { input_tokens, output_tokens, total_tokens } (LangChain.js)
//   2. res.response_metadata.tokenUsage { promptTokens, completionTokens, totalTokens }
//   3. res.usage { prompt_tokens, completion_tokens, total_tokens } (raw OpenAI)
//
// Kontrak: extractUsage(unknown) -> { input_tokens, output_tokens, total_tokens } | null
//   - null bila provider tidak mengirim usage (jangan gagalkan chat).
//   - TIDAK pernah throw, TIDAK pernah log PII.
// ============================================================

function toNonNegativeInt(v) {
    const n = typeof v === "string" && v.trim() !== "" ? Number(v) : v;
    if (typeof n !== "number" || !Number.isFinite(n) || n < 0) return null;
    return Math.floor(n);
}

/**
 * @param {unknown} res respons AIMessage dari model.invoke()
 * @returns {{ input_tokens: number, output_tokens: number, total_tokens: number } | null}
 */
export function extractUsage(res) {
    try {
        if (!res || typeof res !== "object") return null;

        // 1. LangChain.js modern: usage_metadata
        const meta = res.usage_metadata;
        if (meta && typeof meta === "object") {
            const input = toNonNegativeInt(meta.input_tokens);
            const output = toNonNegativeInt(meta.output_tokens);
            const total = toNonNegativeInt(meta.total_tokens);
            if (input !== null || output !== null || total !== null) {
                const i = input ?? 0;
                const o = output ?? 0;
                return { input_tokens: i, output_tokens: o, total_tokens: total ?? i + o };
            }
        }

        // 2. response_metadata.tokenUsage (camelCase OpenAI)
        const tu = res.response_metadata?.tokenUsage;
        if (tu && typeof tu === "object") {
            const input = toNonNegativeInt(tu.promptTokens ?? tu.prompt_tokens);
            const output = toNonNegativeInt(tu.completionTokens ?? tu.completion_tokens);
            const total = toNonNegativeInt(tu.totalTokens ?? tu.total_tokens);
            if (input !== null || output !== null || total !== null) {
                const i = input ?? 0;
                const o = output ?? 0;
                return { input_tokens: i, output_tokens: o, total_tokens: total ?? i + o };
            }
        }

        // 3. Raw OpenAI: usage (snake_case)
        const raw = res.usage;
        if (raw && typeof raw === "object") {
            const input = toNonNegativeInt(raw.prompt_tokens);
            const output = toNonNegativeInt(raw.completion_tokens);
            const total = toNonNegativeInt(raw.total_tokens);
            if (input !== null || output !== null || total !== null) {
                const i = input ?? 0;
                const o = output ?? 0;
                return { input_tokens: i, output_tokens: o, total_tokens: total ?? i + o };
            }
        }

        return null;
    } catch {
        return null;
    }
}
