import { describe, expect, it } from "vitest";
import {
    assessLiquidation,
    classifyLiquidationSeverity,
    computeDropToLiqPct,
    computeLiquidationScore,
    convertHealthFactor
} from "./health-factor";

describe("health-factor: convertHealthFactor", () =>
{
    it("shouldConvertOnchainHealthFactor", () =>
    {
        // Aave v3 Pool.getUserAccountData 의 healthFactor 는 1e18 스케일 정수.
        const onchain = 1250000000000000000n; // 1.25e18

        expect(convertHealthFactor(onchain)).toBeCloseTo(1.25, 9);
    });
});

describe("health-factor: computeDropToLiqPct", () =>
{
    it("shouldComputeDropToLiqForSingleCollateral", () =>
    {
        // dropToLiq% = (1 - 1/HF) * 100. HF=1.25 → 20% (단일담보 근사).
        expect(computeDropToLiqPct(1.25)).toBeCloseTo(20, 9);
    });
});

describe("health-factor: classifyLiquidationSeverity", () =>
{
    it("shouldClassifyHealthyAsInfo", () =>
    {
        // HF≥1.5 → INFO.
        expect(classifyLiquidationSeverity({ hf: 1.6 })).toBe("INFO");
    });

    it("shouldClassifyModerateAsWatch", () =>
    {
        // 1.3≤HF<1.5 → WATCH.
        expect(classifyLiquidationSeverity({ hf: 1.4 })).toBe("WATCH");
    });

    it("shouldClassifyNearAsWarn", () =>
    {
        // 1.1≤HF<1.3 → WARN.
        expect(classifyLiquidationSeverity({ hf: 1.2 })).toBe("WARN");
    });

    it("shouldClassifyByDropToLiqAsWarn", () =>
    {
        // HF만 보면 WATCH(1.45)지만 dropToLiqPct<15 면 WARN 으로 격상(다중담보 케이스).
        expect(classifyLiquidationSeverity({ hf: 1.45, dropToLiqPct: 10 })).toBe("WARN");
    });

    it("shouldClassifyImminentAsUrgent", () =>
    {
        // HF<1.1 → URGENT.
        expect(classifyLiquidationSeverity({ hf: 1.05 })).toBe("URGENT");
    });

    it("shouldEscalateOnSharpDrop", () =>
    {
        // HF=1.4 단독이면 WATCH지만, 1스캔에 ΔHF>0.15 급락 시 디바운스 우회 URGENT.
        expect(classifyLiquidationSeverity({ hf: 1.4, deltaHf: 0.2 })).toBe("URGENT");
    });
});

describe("health-factor: computeLiquidationScore", () =>
{
    it("shouldComputeLiquidationScore", () =>
    {
        // score = clamp(round((1.5-HF)/0.5*100), 0, 100).
        expect(computeLiquidationScore(1.25)).toBe(50);
        expect(computeLiquidationScore(1.5)).toBe(0);
        expect(computeLiquidationScore(1.0)).toBe(100);
        expect(computeLiquidationScore(2.0)).toBe(0);   // 하한 clamp
        expect(computeLiquidationScore(0.9)).toBe(100); // 상한 clamp
    });
});

describe("health-factor: assessLiquidation", () =>
{
    it("shouldEmitStaleNotHealthyOnRpcFailure", () =>
    {
        // 정직성: RPC 실패를 정상(INFO)으로 간주 금지 → state=STALE, severity≠INFO.
        const assessment = assessLiquidation({ rpcOk: false });

        expect(assessment.state).toBe("STALE");
        expect(assessment.severity).not.toBe("INFO");
    });
});
