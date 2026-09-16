import { describe, expect, it } from "vitest";
import { sanitizeAiOutput, sanitizeTransaction } from "../lib/validate.js";

describe("sanitizeAiOutput (validasi profesional output AI)", () => {
    it("terima output valid apa adanya", () => {
        const out = sanitizeAiOutput({
            reply: "Siap, dicatat ya!",
            transaction: {
                title: "Nasi goreng",
                amount: 25000,
                type: "expense",
                category: "Makanan & Minuman",
                transaction_date: "2026-09-16",
            },
        });
        expect(out?.reply).toBe("Siap, dicatat ya!");
        expect(out?.transaction).toMatchObject({ title: "Nasi goreng", amount: 25000 });
    });

    it("koersi amount string numerik (kebiasaan LLM)", () => {
        const out = sanitizeAiOutput({
            reply: "Ok",
            transaction: {
                title: "Kopi",
                amount: "15000",
                type: "expense",
                category: "Makanan & Minuman",
                transaction_date: "2026-09-16",
            },
        });
        expect(out?.transaction?.amount).toBe(15000);
    });

    it("terima alias date -> transaction_date", () => {
        const out = sanitizeTransaction({
            title: "Gaji",
            amount: 5000000,
            type: "income",
            category: "Gaji",
            date: "2026-09-01",
        });
        expect(out?.transaction_date).toBe("2026-09-01");
    });

    it("transaction invalid menjadi null tapi reply tetap hidup", () => {
        const out = sanitizeAiOutput({ reply: "Halo!", transaction: { title: "", amount: -5 } });
        expect(out).toMatchObject({ reply: "Halo!", transaction: null });
    });

    it("tolak kategori ngawur, type ngawur, amount over-max", () => {
        expect(
            sanitizeTransaction({ title: "X", amount: 10000, type: "keluar", category: "Makanan & Minuman" })
        ).toBeNull();
        expect(
            sanitizeTransaction({ title: "X", amount: 10000, type: "expense", category: "Jajan Sembarangan" })
        ).toBeNull();
        expect(
            sanitizeTransaction({ title: "X", amount: 99999999999999, type: "expense", category: "Lainnya" })
        ).toBeNull();
        expect(sanitizeTransaction({ title: "X", amount: 0, type: "expense", category: "Lainnya" })).toBeNull();
    });

    it("tolak reply kosong tanpa throw (pemicu retry)", () => {
        expect(sanitizeAiOutput({ reply: "   ", transaction: null })).toBeNull();
        expect(sanitizeAiOutput({ transaction: null })).toBeNull();
        expect(sanitizeAiOutput(null)).toBeNull();
        expect(sanitizeAiOutput("halo")).toBeNull();
    });
});
