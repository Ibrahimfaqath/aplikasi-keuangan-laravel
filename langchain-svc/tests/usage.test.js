import { describe, expect, it } from "vitest";
import { extractUsage } from "../lib/usage.js";

describe("extractUsage (observability token)", () => {
    it("baca usage_metadata LangChain.js", () => {
        const out = extractUsage({
            content: "halo",
            usage_metadata: { input_tokens: 120, output_tokens: 30, total_tokens: 150 },
        });
        expect(out).toEqual({ input_tokens: 120, output_tokens: 30, total_tokens: 150 });
    });

    it("baca response_metadata.tokenUsage camelCase", () => {
        const out = extractUsage({
            content: "halo",
            response_metadata: { tokenUsage: { promptTokens: 50, completionTokens: 10, totalTokens: 60 } },
        });
        expect(out).toEqual({ input_tokens: 50, output_tokens: 10, total_tokens: 60 });
    });

    it("baca raw OpenAI usage snake_case", () => {
        const out = extractUsage({
            usage: { prompt_tokens: 200, completion_tokens: 40, total_tokens: 240 },
        });
        expect(out).toEqual({ input_tokens: 200, output_tokens: 40, total_tokens: 240 });
    });

    it("hitung total bila tidak ada", () => {
        const out = extractUsage({ usage_metadata: { input_tokens: 10, output_tokens: 5 } });
        expect(out?.total_tokens).toBe(15);
    });

    it("kembalikan null bila tidak ada usage, tanpa throw", () => {
        expect(extractUsage({ content: "halo" })).toBeNull();
        expect(extractUsage(null)).toBeNull();
        expect(extractUsage(undefined)).toBeNull();
        expect(extractUsage("halo")).toBeNull();
        expect(extractUsage({ usage_metadata: { input_tokens: -5 } })).toBeNull();
    });
});
