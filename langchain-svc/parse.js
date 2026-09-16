// ============================================================
// BELAJAR LANGCHAIN — Langkah 2: Sambungkan ke KiosAPI beneran
//
// Sama persis seperti parse.js versi 1 (model suara), tapi kini
// bagian "MODEL" diisi ChatOpenAI yang menunjuk ke KiosAPI kamu.
// Kalau kamu bandingkan, baris yang berubah cuma 1 blok saja.
//
// Jalanin:    node --env-file=.env parse.js
// ============================================================

import { ChatOpenAI } from "@langchain/openai";
import { PromptTemplate } from "@langchain/core/prompts";
import { JsonOutputParser } from "@langchain/core/output_parsers";

const apiKey = process.env.KIOSAPI_API_KEY;

if (!apiKey) {
    console.error("Belum ada key. Buat file .env berisi: KIOSAPI_API_KEY=sk-...");
    process.exit(1);
}

// ------------------------------------------------------------
// 1) CETAKAN (PromptTemplate) — sama seperti versi 1
// ------------------------------------------------------------
const template = PromptTemplate.fromTemplate(`
Kamu adalah kasir aplikasi dompetku. Dari pesan user, tentukan transaksinya.

Pesan user: {pesan}

Kategori yang valid:
Makanan & Minuman, Transportasi, Tagihan & Utilitas, Belanja, Hiburan,
Kesehatan, Pendidikan, Keluarga, Gaji, Bonus, Bisnis, Investasi, Hadiah, Lainnya.

Keluarkan HANYA JSON dengan bentuk:
{{"title": "...", "amount": 25000, "type": "expense", "category": "Makanan & Minuman"}}
`);

// ------------------------------------------------------------
// 2) MODEL — SEKARANG ASLI, bukan tiruan
//    ChatOpenAI = adaptor OpenAI-compatible. KiosAPI cocok, jadi
//    tinggal kasih baseURL. Catatan: tanpa "/chat/completions".
// ------------------------------------------------------------
const model = new ChatOpenAI({
    apiKey,
    model: process.env.KIOSAPI_MODEL || "agnes-2.5-flash",
    temperature: 0,
    maxTokens: 1024,
    configuration: {
        baseURL: "https://kiosapi.com/v1",
    },
});

// ------------------------------------------------------------
// 3) PARSER — memastikan keluar hanya JSON
// ------------------------------------------------------------
const parser = new JsonOutputParser();

// RANTAI: isi cetakan -> tanya model -> wajib JSON
const chain = template.pipe(model).pipe(parser);

// JALANKAN — tugasnya sama seperti TransactionParser di project kamu
const pesan = process.argv[2] ?? "aku tadi beli nasi goreng ayam 25 ribu";

console.log("Pesan:", pesan);
const mulai = performance.now();

try {
    const hasil = await chain.invoke({ pesan });

    console.log("Hasil (JSON):");
    console.log(hasil);
    console.log("\nKategori:", hasil.category);
    console.log("Lama proses:", Math.round(performance.now() - mulai), "ms");
} catch (err) {
    console.error("Gagal memanggil KiosAPI:");
    console.error(err.message || err);
}