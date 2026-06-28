/**
 * 위험 설명 생성 — 모델 출력에 환각 방어(sources 그라운딩·금지동사 post-filter)와
 * refusal 폴백을 적용한다. architecture.md §3.
 */
import type { DetectionSeverity, ExplainRequest, Model, ModelUsage } from "./model";

export type ExplanationSeverity = "info" | "warning" | "critical";

/** 탐지 severity → 설명 severity. INFO/WATCH→info, WARN→warning, URGENT→critical. */
export function mapDetectionSeverity(severity: DetectionSeverity): ExplanationSeverity
{
    switch (severity)
    {
        case "URGENT":
            return "critical";
        case "WARN":
            return "warning";
        default:
            return "info";
    }
}

export interface ChecklistItem
{
    item: string;
    kind: "verify" | "monitor" | "revoke_link";
}

export interface RiskExplanationSource
{
    factKey: string;
    value: string;
}

export interface RiskExplanation
{
    severity: ExplanationSeverity;
    plainExplanation: string;
    whyItMatters: string;
    checkList: ChecklistItem[];
    confidence: number;
    sources: RiskExplanationSource[];
}

export interface RiskExplanationResult
{
    explanation: RiskExplanation;
    /** sources.factKey 가 모두 입력 facts 키에 존재하는지(환각 방어). */
    grounded: boolean;
    /** 그라운딩 위반·금지동사·refusal 로 generic 설명으로 대체했는지. */
    fallback: boolean;
    usage: ModelUsage;
}

/** 모든 source.factKey 가 입력 facts 의 키여야 한다(인용 환각 차단). */
function isGrounded(explanation: RiskExplanation, facts: ExplainRequest["facts"]): boolean
{
    return explanation.sources.every((source) => Object.prototype.hasOwnProperty.call(facts, source.factKey));
}

/** 비자문 불변식: 매매권유 동사는 금지. */
const FORBIDDEN_ADVISORY_PATTERN = /매수|매도|사세요|파세요|팔아라|팔아|사라|\b(buy|sell|swap|long|short)\b/i;

function containsForbiddenAdvisory(explanation: RiskExplanation): boolean
{
    const text = [
        explanation.plainExplanation,
        explanation.whyItMatters,
        ...explanation.checkList.map((entry) => entry.item)
    ].join(" ");

    return FORBIDDEN_ADVISORY_PATTERN.test(text);
}

/** 모델 출력을 신뢰할 수 없을 때 쓰는 안전한 템플릿 설명. sources 는 입력 facts 에서만 구성. */
function genericExplanation(request: ExplainRequest): RiskExplanation
{
    return {
        severity: mapDetectionSeverity(request.severity),
        plainExplanation: "위험 신호가 감지되었습니다. 아래 점검 항목을 직접 확인하세요.",
        whyItMatters: "감지된 사실은 지갑 자산의 안전과 관련될 수 있습니다.",
        checkList: [{ item: "감지된 항목을 직접 점검하세요.", kind: "verify" }],
        confidence: 0,
        sources: Object.entries(request.facts).map(([factKey, value]) => ({ factKey, value: String(value) }))
    };
}

export async function explainRisk(request: ExplainRequest, model: Model): Promise<RiskExplanationResult>
{
    const result = await model.generate(request);

    // refusal 선검사: content 가 비거나 신뢰 불가 → parsed 를 읽지 않고 generic 폴백.
    if (result.stopReason === "refusal")
    {
        return { explanation: genericExplanation(request), grounded: false, fallback: true, usage: result.usage };
    }

    const explanation = result.parsed as RiskExplanation;
    const grounded = isGrounded(explanation, request.facts);

    if (!grounded || containsForbiddenAdvisory(explanation))
    {
        return { explanation: genericExplanation(request), grounded, fallback: true, usage: result.usage };
    }

    return { explanation, grounded, fallback: false, usage: result.usage };
}
