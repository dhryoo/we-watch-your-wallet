/**
 * 모델 호출 비용 추정(USD). architecture.md §3 (알림당 비용 통제).
 * 단가는 per-MTok. Haiku 4.5: $1 in / $5 out, 캐시 쓰기 1.25×, 캐시 읽기 0.1×.
 */
import { HAIKU_MODEL_ID, type ModelUsage } from "./model";

export interface ModelPricing
{
    inputPerMTok: number;
    outputPerMTok: number;
    cacheWritePerMTok: number;
    cacheReadPerMTok: number;
}

export const MODEL_PRICING: Record<string, ModelPricing> = {
    [HAIKU_MODEL_ID]: {
        inputPerMTok: 1.0,
        outputPerMTok: 5.0,
        cacheWritePerMTok: 1.25,
        cacheReadPerMTok: 0.1
    }
};

const PER_MTOK = 1_000_000;

export function estimateCostUsd(usage: ModelUsage, modelId: string): number
{
    const pricing = MODEL_PRICING[modelId];

    if (pricing === undefined)
    {
        throw new Error(`Unknown model pricing: ${modelId}`);
    }

    const cacheWrite = usage.cache_creation_input_tokens ?? 0;
    const cacheRead = usage.cache_read_input_tokens ?? 0;

    const total =
        usage.input_tokens * pricing.inputPerMTok +
        usage.output_tokens * pricing.outputPerMTok +
        cacheWrite * pricing.cacheWritePerMTok +
        cacheRead * pricing.cacheReadPerMTok;

    return total / PER_MTOK;
}
