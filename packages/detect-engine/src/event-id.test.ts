import { describe, expect, it } from "vitest";
import { generateEventId } from "./event-id";

describe("event-id: generateEventId", () =>
{
    it("shouldGenerateDeterministicEventId", () =>
    {
        const base = {
            detectorId: "LIQUIDATION",
            walletId: "w1",
            subjectKey: "aave:0xabc",
            bucketMs: 1000
        };

        const id1 = generateEventId({ ...base, ts: 1_234_500 }); // bucket 1234
        const id2 = generateEventId({ ...base, ts: 1_234_999 }); // 같은 bucket 1234
        const id3 = generateEventId({ ...base, ts: 1_235_000 }); // 다음 bucket 1235

        expect(id1).toBe(id2);     // 같은 버킷 → 같은 id (멱등)
        expect(id3).not.toBe(id1); // 다른 버킷 → 다른 id
    });
});
