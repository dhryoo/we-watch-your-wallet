import { describe, expect, it } from "vitest";
import { estimateCostUsd } from "./cost";
import { HAIKU_MODEL_ID } from "./model";

describe("cost: estimateCostUsd", () =>
{
    it("shouldEstimateCostUsdForHaiku", () =>
    {
        // Haiku 4.5: $1/MTok in, $5/MTok out.
        // 1000 in → 0.001, 500 out → 0.0025 ⇒ 0.0035
        expect(estimateCostUsd({ input_tokens: 1000, output_tokens: 500 }, HAIKU_MODEL_ID))
            .toBeCloseTo(0.0035, 9);

        // 캐시 읽기 2000 @ $0.1/MTok = 0.0002 ⇒ 0.0037
        expect(estimateCostUsd(
            { input_tokens: 1000, output_tokens: 500, cache_read_input_tokens: 2000 },
            HAIKU_MODEL_ID
        )).toBeCloseTo(0.0037, 9);
    });
});
