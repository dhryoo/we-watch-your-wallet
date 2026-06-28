export type Severity = "INFO" | "WATCH" | "WARN" | "URGENT";

export type DetectorId = "LIQUIDATION" | "DEPEG" | "APPROVAL" | "RUG";

/**
 * 비자문 불변식: 노출 가능한 action 은 검토(REVIEW)·안내(INFORM) 뿐.
 * 매매권유(BUY/SELL/SWAP/CLOSE)는 타입에 구조적으로 부재 → 자문 불가. architecture.md §1.
 */
export const DETECTION_ACTIONS = ["REVIEW", "INFORM"] as const;
export type DetectionAction = (typeof DETECTION_ACTIONS)[number];

export type DetectionState = "PENDING" | "CONFIRMED" | "RESOLVED";

export interface DetectionEvent
{
    id: string;            // hash(detectorId|walletId|subjectKey|floor(ts/bucket)) — 멱등
    detectorId: DetectorId;
    walletId: string;
    chainId: number;
    subjectKey: string;
    severity: Severity;
    score: number;         // 0~100
    facts: Record<string, number | string>;
    recommendation: string[];   // "점검 항목"(명령 아님)
    action: DetectionAction;
    revokeLink?: string;
    state: DetectionState;
}
