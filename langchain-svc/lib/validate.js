// ============================================================
// validate — sanitasi & validasi output AI (pure JS, tanpa dep).
//
// Masalah yang diselesaikan (gratis, tanpa breaking change):
//   - extractJson() hanya memastikan ada field `reply` string,
//     tapi `transaction` lolos mentah apa adanya (bisa amount string,
//     type ngawur, kategori invalid, tanggal aneh).
//   - Akibatnya Laravel harus menebak, dan reply bisa bilang
//     "tercatat" padahal transaction-nya null.
//
// Kontrak:
//   sanitizeAiOutput(unknown) -> { reply, transaction } | null
//   - null = tidak bisa dipakai sama sekali (reply kosong) -> panggil retry.
//   - transaction = null bila kandidat tidak valid (tetap balas chat biasa).
//   - TIDAK pernah throw.
//   - Aturan disamakan dengan PHP:
//     App\Services\AiAssistantService::normalizeCandidate() +
//     App\Models\Transaction::allCategories() +
//     AiController validation max:999999999999.99
// ============================================================

const VALID_TYPES = new Set(["income", "expense"]);

// Harus identik dengan Transaction::allCategories() (PHP).
const VALID_CATEGORIES = new Set([
    "Gaji",
    "Bonus",
    "Bisnis",
    "Investasi",
    "Hadiah",
    "Lainnya",
    "Makanan & Minuman",
    "Transportasi",
    "Tagihan & Utilitas",
    "Belanja",
    "Hiburan",
    "Kesehatan",
    "Pendidikan",
    "Keluarga",
]);

const MAX_AMOUNT = 999999999999.99;
const MAX_TITLE_CHARS = 255;
const MAX_REPLY_CHARS = 4000;

function toValidDateOrToday(value) {
    if (typeof value === "string" || typeof value === "number") {
        const d = new Date(value);
        if (!Number.isNaN(d.getTime())) {
            return d.toISOString().slice(0, 10);
        }
    }
    return new Date().toISOString().slice(0, 10);
}

function toAmount(value) {
    if (typeof value === "number") return value;
    // AI kadang mengembalikan "25000" atau "25.000" — coba koersi aman.
    if (typeof value === "string") {
        const cleaned = value.trim().replace(/\./g, "").replace(",", ".");
        if (cleaned !== "" && Number.isFinite(Number(cleaned))) {
            return Number(cleaned);
        }
    }
    return NaN;
}

export function sanitizeTransaction(candidate) {
    if (!candidate || typeof candidate !== "object" || Array.isArray(candidate)) {
        return null;
    }

    const title = typeof candidate.title === "string" ? candidate.title.trim().slice(0, MAX_TITLE_CHARS) : "";
    const amount = toAmount(candidate.amount);
    const type = typeof candidate.type === "string" ? candidate.type.trim() : "";
    const category = typeof candidate.category === "string" ? candidate.category.trim() : "";
    const rawDate = candidate.transaction_date ?? candidate.date ?? null;

    if (title === "") return null;
    if (!Number.isFinite(amount) || amount < 1 || amount > MAX_AMOUNT) return null;
    if (!VALID_TYPES.has(type)) return null;
    if (!VALID_CATEGORIES.has(category)) return null;

    return {
        title,
        amount: Math.round(amount * 100) / 100,
        type,
        category,
        transaction_date: toValidDateOrToday(rawDate),
    };
}

/**
 * @param {unknown} obj hasil extractJson()
 * @returns {{ reply: string, transaction: object | null } | null}
 */
export function sanitizeAiOutput(obj) {
    if (!obj || typeof obj !== "object" || Array.isArray(obj)) return null;

    const replyRaw = obj.reply;
    if (typeof replyRaw !== "string") return null;
    const reply = replyRaw.trim().slice(0, MAX_REPLY_CHARS);
    if (reply === "") return null;

    return {
        reply,
        transaction: sanitizeTransaction(obj.transaction),
    };
}
