import Anthropic from "@anthropic-ai/sdk";
import { describe, expect, it } from "vitest";
import { AnthropicModel } from "./anthropic-model";
import { HAIKU_MODEL_ID } from "./model";

describe("anthropic-model: AnthropicModel", () =>
{
    it("shouldDefaultToHaikuModel", () =>
    {
        const model = new AnthropicModel(new Anthropic({ apiKey: "test-key" }));

        expect(model.modelId).toBe(HAIKU_MODEL_ID);
        expect(model.modelId).toBe("claude-haiku-4-5");
    });
});
