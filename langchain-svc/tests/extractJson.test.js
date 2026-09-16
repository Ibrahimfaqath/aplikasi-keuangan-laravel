import { describe, expect, it } from "vitest";
import { extractJson } from "../lib/extractJson.js";

describe("extractJson", () => {
    it("parse JSON murni", () => {
        const out = extractJson('{"reply":"Halo","transaction":null}');
        expect(out).toMatchObject({ reply: "Halo", transaction: null });
    });

    it("parse fenced ```json block (kebiasaan reasoning model)", () => {
        const raw = 'Tentu!\n```json\n{"reply":"Siap","transaction":{"title":"Nasi goreng","amount":25000}}\n```';
        expect(extractJson(raw)?.reply).toBe("Siap");
    });

    it("parse fenced tanpa label bahasa", () => {
        const raw = '```\n{"reply":"Ok"}\n```';
        expect(extractJson(raw)?.reply).toBe("Ok");
    });

    it("parse teks kotor di sekitar JSON", () => {
        const raw = 'Berikut hasilnya: {"reply":"Tercatat ya!","transaction":null} semoga membantu 🙏';
        expect(extractJson(raw)?.reply).toBe("Tercatat ya!");
    });

    it("tolak bila tidak ada field reply", () => {
        expect(extractJson('{"transaction":null}')).toBeNull();
        expect(extractJson('{"foo":1}')).toBeNull();
    });

    it("tolak input invalid tanpa throw", () => {
        expect(extractJson("")).toBeNull();
        expect(extractJson("   ")).toBeNull();
        expect(extractJson("halo dunia")).toBeNull();
        expect(extractJson("{bukan json")).toBeNull();
        expect(extractJson(null)).toBeNull();
        expect(extractJson(undefined)).toBeNull();
        expect(extractJson(123)).toBeNull();
    });
});
