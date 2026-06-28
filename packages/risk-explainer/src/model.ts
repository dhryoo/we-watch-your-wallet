/**
 * Claude 모델 추상화 — 탐지 이벤트의 facts 를 받아 구조화 설명을 생성한다.
 * FakeModel 로 실 API 없이 TDD. architecture.md §3.
 */
import type { Severity } from "@watchtower/detect-engine";

/** P0 모델: Haiku 4.5 (무료 훅은 무조건 Haiku). */
export const HAIKU_MODEL_ID = "claude-haiku-4-5";

/** 탐지 엔진의 severity 를 그대로 입력으로 받는다. */
export type DetectionSeverity = Severity;

export type ModelStopReason =
    | "end_turn"
    | "max_tokens"
    | "stop_sequence"
    | "tool_use"
    | "pause_turn"
    | "refusal";

export interface ModelUsage
{
    input_tokens: number;
    output_tokens: number;
    cache_creation_input_tokens?: number;
    cache_read_input_tokens?: number;
}

export interface ModelResult
{
    stopReason: ModelStopReason;
    /** 구조화 출력으로 파싱된 RiskExplanation 후보 (refusal 시 null 가능). */
    parsed: unknown;
    usage: ModelUsage;
}

export interface ExplainRequest
{
    severity: DetectionSeverity;
    facts: Record<string, number | string>;
}

export interface Model
{
    readonly modelId: string;
    generate(request: ExplainRequest): Promise<ModelResult>;
}

/** 고정 출력을 돌려주는 테스트용 모델. */
export class FakeModel implements Model
{
    constructor(private readonly result: ModelResult, readonly modelId: string = HAIKU_MODEL_ID)
    {
    }

    async generate(_request: ExplainRequest): Promise<ModelResult>
    {
        return this.result;
    }
}
