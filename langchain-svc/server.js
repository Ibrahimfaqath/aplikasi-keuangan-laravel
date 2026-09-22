// ============================================================
// DompetKu — LangChain Service (Express + LangChain.js)
//
// POST /chat    { "system": "...", "message": "...", "history": [{role, content}] }
//               -> { "reply": "...", "transaction": {...} | null, "timing_ms": N,
//                    "usage": {input_tokens, output_tokens, total_tokens} | null,
//                    "prompt_version": "v1.1.0", "model": "..." }
// GET  /health  -> { "status": "ok", "model": "...", "prompt_version": "...", "tracing": bool }
//
// Keamanan berlapis (jangan andalkan satu lapis saja):
//   1. Bind 127.0.0.1 — hanya bisa diakses dari server itu sendiri.
//   2. Internal token (opsional tapi SANGAT disarankan):
//      set LANGCHAIN_INTERNAL_TOKEN di sini DAN di Laravel
//      (.env: LANGCHAIN_SERVICE_TOKEN=...). Laravel mengirim
//      header `x-internal-token`, service menolak bila tidak cocok.
//   3. Rate-limit 30/menit per IP (selaras throttle:ai di Laravel).
//   4. Validasi panjang input agar tidak jebol token/DoS.
//
// Jalanin:   npm start   ( = node --env-file=.env server.js )
// Dev:       npm run dev
// Test:      npm test
// ============================================================

import crypto from "node:crypto";
import express from "express";
import helmet from "helmet";
import cors from "cors";
import rateLimit from "express-rate-limit";
import { ChatOpenAI } from "@langchain/openai";
import {
    ChatPromptTemplate,
    HumanMessagePromptTemplate,
    SystemMessagePromptTemplate,
    MessagesPlaceholder,
} from "@langchain/core/prompts";
import { HumanMessage, AIMessage } from "@langchain/core/messages";
import { extractJson } from "./lib/extractJson.js";
import { sanitizeAiOutput } from "./lib/validate.js";
import { extractUsage } from "./lib/usage.js";
import { normalizeHistory } from "./lib/history.js";
import { existsSync, readFileSync } from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

// --- LOADER .env (fallback) -------------------------------------------------
// Env vars dari Passenger/shell tetap prioritas; file .env hanya mengisi yang
// belum ada. Ini membuat service tetap jalan walau user tidak sempat set
// "Add Variable" di cPanel (cukup upload .env berisi konfigurasi).
const APP_DIR = path.dirname(fileURLToPath(import.meta.url));
const ENV_FILE = path.join(APP_DIR, ".env");
if (existsSync(ENV_FILE)) {
    const raw = readFileSync(ENV_FILE, "utf8");
    for (const line of raw.split(/\r?\n/)) {
        const m = line.match(/^\s*([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*?)\s*$/);
        if (!m || /^\s*#/.test(line)) {
            continue;
        }
        const key = m[1];
        let val = m[2].trim();
        if (
            (val.startsWith('"') && val.endsWith('"')) ||
            (val.startsWith("'") && val.endsWith("'"))
        ) {
            val = val.slice(1, -1);
        }
        if (process.env[key] === undefined) {
            process.env[key] = val;
        }
    }
}

// --- VALIDASI ENV (fail-fast, jangan jalan setengah jadi) ------------------
const apiKey = process.env.KIOSAPI_API_KEY;
if (!apiKey) {
    console.error("[langchain-svc] FATAL: .env kurang KIOSAPI_API_KEY");
    process.exit(1);
}

const MODEL = process.env.KIOSAPI_MODEL || "agnes-2.5-flash";
const PORT_RAW = Number(process.env.PORT || 8787);
if (!Number.isInteger(PORT_RAW) || PORT_RAW < 1 || PORT_RAW > 65535) {
    console.error(`[langchain-svc] FATAL: PORT tidak valid: ${process.env.PORT}`);
    process.exit(1);
}
const PORT = PORT_RAW;

// Token internal antar-service. Bila kosong -> mode DEV (boleh tanpa auth,
// tapi log warning keras agar tidak lupa di production).
const INTERNAL_TOKEN = process.env.LANGCHAIN_INTERNAL_TOKEN || "";
if (!INTERNAL_TOKEN) {
    console.warn(
        "[langchain-svc] WARNING: LANGCHAIN_INTERNAL_TOKEN kosong — /chat terbuka untuk siapa saja di localhost. " +
            "Set token ini di production dan di Laravel (LANGCHAIN_SERVICE_TOKEN)."
    );
}

const AI_TIMEOUT_MS = Number(process.env.AI_TIMEOUT_MS || 60_000);
const MAX_SYSTEM_CHARS = 20_000; // system prompt Laravel (20 transaksi terakhir) muat lega
const MAX_MESSAGE_CHARS = 2000; // selaras validasi AiController: message max:2000

// Versi prompt — naikkan tiap ubah template di bawah agar log & debug bisa
// membedakan output prompt lama vs baru. Gratis, tanpa service tambahan.
const PROMPT_VERSION = process.env.PROMPT_VERSION || "v1.2.0";

// LangSmith tracing (opsional, ada free-tier). Aktif bila env berikut di-set:
//   LANGCHAIN_TRACING_V2=true, LANGCHAIN_API_KEY=..., LANGCHAIN_PROJECT=...
// LangChain.js membaca env ini otomatis — tidak perlu kode tambahan.
// Kami hanya mendeteksi & melapor di /health agar mudah cek.
const TRACING_ENABLED = process.env.LANGCHAIN_TRACING_V2 === "true";

const CATEGORIES_HINT =
    "Gaji, Bonus, Bisnis, Investasi, Hadiah, Lainnya, Makanan & Minuman, " +
    "Transportasi, Tagihan & Utilitas, Belanja, Hiburan, Kesehatan, Pendidikan, Keluarga";

// Tanggal "hari ini" pakai zona waktu Indonesia (WIB), terlepas dari timezone
// server. Model sering menebak "hari ini" dari data transaksi; sematkan tanggal
// eksplisit agar pemakaian "hari ini"/"sekarang"/"kemarin" akurat untuk data keuangan.
function buildHariIni() {
    const now = new Date();
    const long = new Intl.DateTimeFormat("id-ID", {
        timeZone: "Asia/Jakarta",
        weekday: "long",
        day: "numeric",
        month: "long",
        year: "numeric",
    }).format(now);
    const iso = new Intl.DateTimeFormat("en-CA", {
        timeZone: "Asia/Jakarta",
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    }).format(now);
    return (
        "HARI INI: " + long + " (" + iso + ").\n" +
        "Rujuk tanggal HARI INI untuk ungkapan \u201chairi ini\u201d, \u201csekarang\u201d, \u201ckemarin\u201d. " +
        "JANGAN menebak tanggal dari data transaksi."
    );
}

// --- MODEL ----------------------------------------------------------------
// deepseek-v4-flash adalah model "reasoning": dia berpikir panjang dulu
// baru menjawab, makanya maxTokens dibuat besar agar JSON tidak terpotong.
const model = new ChatOpenAI({
    apiKey,
    model: MODEL,
    temperature: 0, // deterministik — penting untuk data keuangan
    maxTokens: 4096,
    timeout: AI_TIMEOUT_MS,
    maxRetries: 0, // retry diatur manual di bawah agar instruksi ikut berubah
    configuration: { baseURL: "https://kiosapi.com/v1" },
});

// --- CETAKAN + MEMORY -----------------------------------------------------
// ChatPromptTemplate = versi chat dari PromptTemplate.
// Bedanya: mendukung riwayat multi-turn via MessagesPlaceholder("history").
// Urutan yang benar (standar LangChain):
//   1. System (aturan + data keuangan + format JSON)
//   2. History (10 pertukaran terakhir, disuntik sebagai Human/AIMessage)
//   3. Human (pesan terbaru user)
const prompt = ChatPromptTemplate.fromMessages([
    SystemMessagePromptTemplate.fromTemplate(`{today}

{system}

Kamu punya MEMORI percakapan di bawah (bila ada). Gunakan untuk menjawab
pertanyaan lanjutan seperti "berapa tadi?", "yang itu kapan?", "tambahin lagi".
Jangan mengarang: bila tidak ada di memori maupun data, katakan jujur.

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

Jangan sertakan teks lain di luar objek JSON tersebut.{instructions}`),
    new MessagesPlaceholder("history"),
    HumanMessagePromptTemplate.fromTemplate("{message}"),
]);

// Satu "percobaan": render prompt (system+history+message) -> panggil model -> cari JSON -> sanitasi.
// Mengembalikan { data, usage } agar observability (token) tidak hilang saat retry.
// sanitizeAiOutput memastikan transaction invalid menjadi null (tetap balas chat),
// dan reply kosong dianggap gagal sehingga memicu retry.
async function cobaSekali(payload, historyMessages, signal, categorySet) {
    const rendered = await prompt.invoke({ today: buildHariIni(), ...payload, history: historyMessages });
    const res = await model.invoke(rendered, { signal });

    const text = Array.isArray(res.content)
        ? res.content.map((c) => (typeof c === "string" ? c : (c.text ?? ""))).join("")
        : String(res.content ?? "");
    return { data: sanitizeAiOutput(extractJson(text), categorySet), usage: extractUsage(res) };
}

// Retry 2x: percobaan ke-2 diberi instruksi tegas agar model reasoning
// memotong chain-of-thought dan langsung mengeluarkan JSON.
// History diubah ke HumanMessage/AIMessage di sini (batas sudah di normalizeHistory).
// Mengembalikan { data, usage } — usage diambil dari percobaan yang berhasil.
async function chat(payload, rawHistory, signal, categorySet) {
    const historyMessages = (rawHistory || []).map((h) =>
        h.role === "user" ? new HumanMessage(h.content) : new AIMessage(h.content)
    );

    const attempts = [
        { instructions: "" },
        {
            instructions:
                " PENTING: langsung keluarkan HANYA JSON valid tanpa penjelasan, tanpa markdown, tanpa teks tambahan.",
        },
    ];

    for (let i = 0; i < attempts.length; i++) {
        const { data, usage } = await cobaSekali({ ...payload, ...attempts[i] }, historyMessages, signal, categorySet);
        if (data) return { data, usage };
        console.log(`[retry-${i + 1}] model tidak mengembalikan JSON valid`);
    }

    throw new Error("model tidak mengembalikan JSON yang valid setelah 2 percobaan");
}

    throw new Error("model tidak mengembalikan JSON yang valid setelah 2 percobaan");
}

// --- APP ------------------------------------------------------------------
const app = express();
app.disable("x-powered-by");
app.set("trust proxy", true); // Passenger cPanel menambah X-Forwarded-For; tanpa ini express-rate-limit melempar error
app.use(helmet());
app.use(cors({ origin: false })); // tidak perlu browser cross-origin; Laravel memanggil server-to-server
app.use(express.json({ limit: "32kb" })); // system prompt besar tapi tetap dibatasi

// Passenger cPanel/CloudLinux memasang app pada base URI (contoh /langchain-node)
// dan meneruskan request dengan path LENGKAP. Strip prefix tersebut agar route
// di bawah tetap cocok ("/health", "/chat", "/"). Aman bila prefix tidak ada.
app.use((req, _res, next) => {
    const p = req.path;
    const slash = p.indexOf("/", 1);
    const first = slash === -1 ? p.slice(1) : p.slice(1, slash);
    if (first && first !== "chat" && first !== "health") {
        req.url = p.slice(first.length + 1) || "/";
    }
    next();
});

// Rate-limit selaras Laravel throttle:ai (30/menit per user).
const chatLimiter = rateLimit({
    windowMs: 60_000,
    limit: 30,
    standardHeaders: "draft-8",
    legacyHeaders: false,
    message: { error: "Terlalu banyak permintaan. Coba lagi dalam semenit ya!" },
});

// Auth internal: bandingkan token dengan timingSafeEqual agar tahan timing-attack.
function requireInternalToken(req, res, next) {
    if (!INTERNAL_TOKEN) return next(); // mode DEV
    const got = req.header("x-internal-token") || "";
    const a = Buffer.from(got);
    const b = Buffer.from(INTERNAL_TOKEN);
    const ok = a.length === b.length && a.length > 0 && crypto.timingSafeEqual(a, b);
    if (!ok) return res.status(401).json({ error: "Unauthorized (internal token tidak valid)" });
    return next();
}

app.get("/health", (_req, res) => {
    res.json({ status: "ok", model: MODEL, prompt_version: PROMPT_VERSION, tracing: TRACING_ENABLED });
});

app.get("/", (_req, res) => {
    res.json({ status: "ok", service: "langchain-svc", health: "/health" });
});

app.post("/chat", chatLimiter, requireInternalToken, async (req, res) => {
    const { system, message, history } = req.body || {};

    if (typeof system !== "string" || system.trim() === "") {
        return res.status(400).json({ error: "body harus berisi 'system' (string tak-kosong)" });
    }
    if (typeof message !== "string" || message.trim() === "") {
        return res.status(400).json({ error: "body harus berisi 'message' (string tak-kosong)" });
    }
    // History opsional (memory). Selalu dinormalisasi: maks 20 item, buang role asing.
    const cleanHistory = normalizeHistory(history);
    if (system.length > MAX_SYSTEM_CHARS) {
        return res
            .status(413)
            .json({ error: `system terlalu panjang (maks ${MAX_SYSTEM_CHARS} karakter)` });
    }
    if (message.length > MAX_MESSAGE_CHARS) {
        return res
            .status(413)
            .json({ error: `message terlalu panjang (maks ${MAX_MESSAGE_CHARS} karakter)` });
    }

    // Kategori user (bawaan + custom) dikirim Laravel sebagai string CSV.
    // Dipakai UNTUK MODEL (petunjuk list di prompt) DAN UNTUK VALIDASI/sanitasi
    // kandidat. Bila tidak dikirim (service versi lama / malformed), fallback
    // ke daftar bawaan yang identik dengan Transaction::allCategories().
    const requestedCategories = typeof req.body.categories === "string" ? req.body.categories.trim() : "";
    const categoriesHint = requestedCategories !== "" ? requestedCategories : CATEGORIES_HINT;
    const categorySet = new Set(categoriesHint.split(",").map((s) => s.trim()).filter(Boolean));

    const mulai = performance.now();
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), AI_TIMEOUT_MS);

    try {
        const { data: hasil, usage } = await chat(
            { system: system.trim(), message: message.trim(), categories: categoriesHint },
            cleanHistory,
            controller.signal,
            categorySet
        );

        const timing_ms = Math.round(performance.now() - mulai);
        // Observability gratis: log token + timing TANPA isi pesan / data keuangan.
        console.log(
            `[chat] prompt=${PROMPT_VERSION} timing_ms=${timing_ms} ` +
                `tokens=${usage ? `${usage.input_tokens}/${usage.output_tokens}/${usage.total_tokens}` : "n/a"} ` +
                `has_transaction=${hasil.transaction ? "1" : "0"}`
        );

        return res.json({
            reply: hasil.reply ?? "Maaf, tidak ada balasan dari asisten.",
            transaction: hasil.transaction ?? null,
            timing_ms,
            usage: usage ?? null,
            prompt_version: PROMPT_VERSION,
            model: MODEL,
        });
    } catch (err) {
        // Jangan bocorkan detail internal ke client; log penuh di server saja.
        console.error("Gagal memproses chat:", err?.message || err);
        const isAbort = err?.name === "AbortError";
        return res.status(502).json({
            error: "Gagal menghubungi AI",
            detail: isAbort ? `timeout setelah ${AI_TIMEOUT_MS}ms` : "provider tidak mengembalikan JSON valid",
        });
    } finally {
        clearTimeout(timer);
    }
});

const server = app.listen(PORT, "127.0.0.1", () => {
    console.log(
        `Service LangChain aktif di http://127.0.0.1:${PORT} (model=${MODEL} prompt=${PROMPT_VERSION} tracing=${TRACING_ENABLED ? "on" : "off"})`
    );
});

// Graceful shutdown: selesaikan request berjalan sebelum mati (penting di systemd/PM2/Docker).
function shutdown(signal) {
    console.log(`[${signal}] menutup service...`);
    server.close(() => process.exit(0));
    setTimeout(() => process.exit(1), 10_000).unref();
}
process.on("SIGTERM", () => shutdown("SIGTERM"));
process.on("SIGINT", () => shutdown("SIGINT"));
