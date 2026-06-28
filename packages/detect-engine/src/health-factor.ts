/**
 * 청산(LIQUIDATION) 탐지 — Aave v3 Pool.getUserAccountData 권위값 기반 순수함수.
 * architecture.md §2.1.
 */
import type { Severity } from "./types";

const HF_SCALE = 1e18;

export interface LiquidationInput
{
    hf: number;
    /** 없으면 HF로부터 단일담보 근사로 계산. 다중담보는 외부에서 직접 주입. */
    dropToLiqPct?: number;
    /** 직전 스캔 대비 HF 하락폭(양수=하락). 급락 에스컬레이션 판정용. */
    deltaHf?: number;
}

export type LiquidationState = "OK" | "STALE";

/** 한 스캔의 원시 판독값. rpcOk=false면 HF 미확보(정직성 불변식 적용 대상). */
export interface LiquidationReading
{
    rpcOk: boolean;
    hf?: number;
    dropToLiqPct?: number;
    deltaHf?: number;
}

export interface LiquidationAssessment
{
    state: LiquidationState;
    severity: Severity;
    hf: number | null;
    dropToLiqPct: number | null;
    score: number;
}

/** 1e18 스케일 온체인 healthFactor 정수 → HF 실수. 식 재구현 금지, 단순 스케일 변환. */
export function convertHealthFactor(raw: bigint): number
{
    return Number(raw) / HF_SCALE;
}

/** 청산까지 거리(단일담보 근사): dropToLiq% = (1 - 1/HF) * 100. HF=1.25 → 20%. */
export function computeDropToLiqPct(hf: number): number
{
    return (1 - 1 / hf) * 100;
}

/** 청산 위험도 점수: clamp(round((1.5-HF)/0.5*100), 0, 100). */
export function computeLiquidationScore(hf: number): number
{
    const raw = Math.round(((1.5 - hf) / 0.5) * 100);
    return Math.max(0, Math.min(100, raw));
}

/** HF/근접도/급락 기반 severity 분류. architecture.md §2.1 임계값. */
export function classifyLiquidationSeverity(input: LiquidationInput): Severity
{
    const dropToLiqPct = input.dropToLiqPct ?? computeDropToLiqPct(input.hf);

    // 급락 에스컬레이션: 1스캔에 ΔHF>0.15 하락이면 절대 HF와 무관하게 URGENT(escalateBypass).
    if (input.hf < 1.1 || (input.deltaHf !== undefined && input.deltaHf > 0.15))
    {
        return "URGENT";
    }

    if (input.hf < 1.3 || dropToLiqPct < 15)
    {
        return "WARN";
    }

    if (input.hf < 1.5)
    {
        return "WATCH";
    }

    return "INFO";
}

/**
 * 한 스캔 판독값 → 청산 어세스먼트.
 * 정직성 불변식: RPC 실패 시 마지막 양호값을 정상 간주하지 않고 STALE(비-INFO) 로 표기.
 */
export function assessLiquidation(reading: LiquidationReading): LiquidationAssessment
{
    if (!reading.rpcOk || reading.hf === undefined)
    {
        return {
            state: "STALE",
            severity: "WATCH",
            hf: null,
            dropToLiqPct: null,
            score: 0
        };
    }

    const hf = reading.hf;
    const dropToLiqPct = reading.dropToLiqPct ?? computeDropToLiqPct(hf);

    return {
        state: "OK",
        severity: classifyLiquidationSeverity({ hf, dropToLiqPct, deltaHf: reading.deltaHf }),
        hf,
        dropToLiqPct,
        score: computeLiquidationScore(hf)
    };
}
