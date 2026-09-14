// ============================================================
// BELAJAR LANGCHAIN — Langkah 3: Jadikan service HTTP
//
// LangChain yang tadi (parse.js) kita angkat jadi layanan kecil
// yang bisa dipanggil aplikasi Laravel via HTTP.
//
//    POST /chat    { "system": "...", "message": "..." }
//                  -> { "reply": "...", "transaction": {...} | null }
//    GET  /health  -> { "status": "ok" }
//
// Jalanin:   node --env-file=.env server.js
// ============================================================

import express from "express";
import { ChatOpenAI } from "@langchain/openai";
import { PromptTemplate } from "@langchain/core/prompts";

const apiKey = process.env.KIOSAPI_API_KEY;
if (!apiKey) {
    console.error("File .env kurang KIOSAPI_API_KEY");
    process.exit(1);
}

const PORT = Number(process.env.PORT || 8787);
const CATEGORIES_HINT =
    "Gaji, Bonus, Bisnis, Investasi, Hadiah, Lainnya, Makanan & Minuman, " +
    "Transportasi, Tagihan & Utilitas, Belanja, Hiburan, Kesehatan, Pendidikan, Keluarga";

// --- MODEL --------------------------------------------------------------
// deepseek-v4-flash adalah model "reasoning": dia berbelit dulu (chain of
// thought) baru menjawab. Karena itu alokasi token dibuat besar, supaya
// jawaban JSON tidak terpotong.
const model = new ChatOpenAI({
    apiKey,
    model: process.env.KIOSAPI_MODEL || "deepseek-v4-flash",
    temperature: 0,
    maxTokens: 4096,
    configuration: { baseURL: "https://kiosapi.com/v1" },
});

// --- CETAKAN ------------------------------------------------------------
const template = PromptTemplate.fromTemplate(`
{system}

Pesan user: {message}

Sekarang BALAS. Keluarkan HANYA satu objek JSON dengan dua kunci:
{{
  "reply": "balasan ramah dalam Bahasa Indonesia untuk user",
  "transaction": null atau {{
    "title": "judul transaksi",
    "amount": <angka tanpa titik/koma>,
    "type": "income" atau "expense",
    "category": "kategori valid. Kategori: {categories}",
    "transaction_date": "YYYY-MM-DD"
  }}
}}

Jangan sertakan teks lain di luar objek JSON tersebut.
`);

// Toleransi: model reasoning kadang membungkus jawaban dalam ```json ... ```
// atau menyisipkan teks. Kita cari objek JSON sebaik mungkin.
function extractJson(text) {
    const fenced = text.match(/```(?:json)?\s*([\s\S]*?)```/);
    const candidate = fenced ? fenced[1] : text.match(/\{[\s\S]*\}/)?.[0];

    if (!candidate) return null;

    try {
        const obj = JSON.parse(candidate);
        return obj && typeof obj.reply === "string" ? obj : null;
    } catch {
        return null;
    }
}

// Satu "percobaan": render prompt -> panggil model -> cari JSON.
async function cobaSekali(payload) {
    const rendered = await template.invoke(payload);
    const res = await model.invoke(rendered);

    const text = Array.isArray(res.content) ? res.content.map((c) => c.text ?? "").join("") : String(res.content ?? "");
    return extractJson(text);
}

// Dengan retry: percobaan ke-2 diberi instruksi ekstra agar model
// memotong jawaban panjang (cara jitu menangani model reasoning).
async function chat(payload) {
    for (let attempt = 1; attempt <= 2; attempt++) {
        const hasil = await cobaSekali({
            ...payload,
            instructions: attempt === 2 ? " Persingkat jawaban." : "",
        });

        if (hasil) return hasil;

        console.log(`[retry-${attempt}] model tidak mengembalikan JSON valid`);
    }

    throw new Error("model tidak mengembalikan JSON yang valid setelah 2 percobaan");
}

// --- LAYANAN HTTP --------------------------------------------------------
const app = express();
app.use(express.json());

app.get("/health", (_req, res) => {
    res.json({ status: "ok" });
});

app.post("/chat", async (req, res) => {
    const { system, message } = req.body || {};

    if (typeof system !== "string" || system.trim() === "") {
        return res.status(400).json({ error: "body harus berisi 'system' (string)" });
    }
    if (typeof message !== "string" || message.trim() === "") {
        return res.status(400).json({ error: "body harus berisi 'message' (string)" });
    }

    const mulai = performance.now();

    try {
        const hasil = await chat({ system, message, categories: CATEGORIES_HINT });

        return res.json({
            reply: hasil.reply ?? "Maaf, tidak ada balasan dari asisten.",
            transaction: hasil.transaction ?? null,
            timing_ms: Math.round(performance.now() - mulai),
        });
    } catch (err) {
        console.error("Gagal memproses chat:", err.message || err);
        return res.status(502).json({
            error: "Gagal menghubungi AI",
            detail: err.message || String(err),
        });
    }
});

app.listen(PORT, "127.0.0.1", () => {
    console.log("Service LangChain aktif di http://127.0.0.1:" + PORT);
});