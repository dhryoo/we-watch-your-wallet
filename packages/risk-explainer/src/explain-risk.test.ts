import { describe, expect, it } from "vitest";
import { FakeModel, type ModelResult } from "./model";
import { explainRisk, mapDetectionSeverity, type RiskExplanation } from "./explain-risk";

const VALID: RiskExplanation = {
    severity: "warning",
    plainExplanation: "건강도 지표가 청산 임계값에 근접했습니다.",
    whyItMatters: "추가 가격 하락 시 담보가 청산될 수 있습니다.",
    checkList: [{ item: "담보 비율을 점검하세요.", kind: "monitor" }],
    confidence: 0.7,
    sources: [{ factKey: "healthFactor", value: "1.2" }]
};

function fakeResult(parsed: unknown, stopReason: ModelResult["stopReason"] = "end_turn"): ModelResult
{
    return { stopReason, parsed, usage: { input_tokens: 100, output_tokens: 50 } };
}

describe("explain-risk: explainRisk", () =>
{
    it("shouldProduceStructuredRiskExplanationFromModel", async () =>
    {
        const result = await explainRisk(
            { severity: "WARN", facts: { healthFactor: 1.2 } },
            new FakeModel(fakeResult(VALID))
        );

        expect(result.fallback).toBe(false);
        expect(result.explanation).toEqual(VALID);
    });
});

describe("explain-risk: 환각 방어", () =>
{
    it("shouldGroundSourcesInInputFactKeys", async () =>
    {
        const grounded: RiskExplanation = {
            ...VALID,
            sources: [
                { factKey: "healthFactor", value: "1.2" },
                { factKey: "dropToLiqPct", value: "10" }
            ]
        };

        const result = await explainRisk(
            { severity: "WARN", facts: { healthFactor: 1.2, dropToLiqPct: 10 } },
            new FakeModel(fakeResult(grounded))
        );

        expect(result.grounded).toBe(true);
        expect(result.fallback).toBe(false);
        expect(result.explanation.sources).toEqual(grounded.sources);
    });

    it("shouldRejectHallucinatedSourceFactKey", async () =>
    {
        const hallucinated: RiskExplanation = {
            ...VALID,
            sources: [{ factKey: "totallyMadeUpKey", value: "???" }]
        };

        const result = await explainRisk(
            { severity: "WARN", facts: { healthFactor: 1.2 } },
            new FakeModel(fakeResult(hallucinated))
        );

        expect(result.grounded).toBe(false);
        expect(result.fallback).toBe(true);
        // generic 폴백: 환각 factKey 제거, 입력 facts 만 인용
        expect(result.explanation.sources.map((s) => s.factKey)).toEqual(["healthFactor"]);
    });

    it("shouldPostFilterForbiddenAdvisoryVerbs", async () =>
    {
        // 그라운딩은 통과하지만 매매권유 동사 포함 → 비자문 불변식 위반 → generic 폴백.
        const advisory: RiskExplanation = {
            ...VALID,
            plainExplanation: "지금 바로 전량 매도하세요.",
            sources: [{ factKey: "healthFactor", value: "1.2" }]
        };

        const result = await explainRisk(
            { severity: "WARN", facts: { healthFactor: 1.2 } },
            new FakeModel(fakeResult(advisory))
        );

        expect(result.fallback).toBe(true);
        expect(result.explanation.plainExplanation).not.toContain("매도");
    });

    it("shouldFallbackToGenericOnRefusal", async () =>
    {
        // stop_reason="refusal" → content 없음. throw 없이 generic 폴백.
        const result = await explainRisk(
            { severity: "URGENT", facts: { healthFactor: 1.05 } },
            new FakeModel(fakeResult(null, "refusal"))
        );

        expect(result.fallback).toBe(true);
        expect(result.explanation.severity).toBe("critical"); // URGENT → critical
        expect(result.explanation.sources.map((s) => s.factKey)).toEqual(["healthFactor"]);
    });
});

describe("explain-risk: mapDetectionSeverity", () =>
{
    it("shouldMapDetectionSeverityToExplanationSeverity", () =>
    {
        expect(mapDetectionSeverity("INFO")).toBe("info");
        expect(mapDetectionSeverity("WATCH")).toBe("info");
        expect(mapDetectionSeverity("WARN")).toBe("warning");
        expect(mapDetectionSeverity("URGENT")).toBe("critical");
    });
});
