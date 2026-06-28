/**
 * 위험 Approval(APPROVAL) 탐지 — 결정론적 가중합 순수함수(자문 아님).
 * architecture.md §2.2.
 */
import type { Severity } from "./types";

/** 무제한 승인 기준: allowance ≥ 2^256/2 (= 2^255). */
const UNLIMITED_THRESHOLD = 2n ** 255n;

export function isUnlimitedAllowance(allowance: bigint): boolean
{
    return allowance >= UNLIMITED_THRESHOLD;
}

/** revoke.cash 딥링크(표시 전용, 실행은 사용자 본인 지갑). architecture.md §2.2. */
export function buildRevokeLink(owner: string, chainId: number): string
{
    return `https://revoke.cash/address/${owner}?chainId=${chainId}`;
}

export type SpenderReputation = "MALICIOUS" | "VERIFIED" | "UNVERIFIED";
export type SpenderType = "EOA" | "CONTRACT";

export interface ApprovalInput
{
    owner?: string;
    chainId?: number;
    allowance?: bigint;
    spenderReputation?: SpenderReputation;
    spenderType?: SpenderType;
    isSetApprovalForAll?: boolean;     // NFT
    balanceMultipleOver50x?: boolean;
    highValueToken?: boolean;
    recentlyGranted?: boolean;
}

/** 결정론적 가중합 risk score. architecture.md §2.2 가산표. */
export function scoreApprovalRisk(input: ApprovalInput): number
{
    let score = 0;

    if (input.spenderReputation === "MALICIOUS")
    {
        score += 60;
    }

    if (input.spenderType === "EOA")
    {
        score += 30;
    }

    if (input.spenderReputation === "UNVERIFIED")
    {
        score += 25;
    }

    if (input.allowance !== undefined && isUnlimitedAllowance(input.allowance))
    {
        score += 20;
    }

    if (input.isSetApprovalForAll === true)
    {
        score += 20;
    }

    if (input.balanceMultipleOver50x === true)
    {
        score += 15;
    }

    if (input.highValueToken === true)
    {
        score += 10;
    }

    if (input.recentlyGranted === true)
    {
        score += 10;
    }

    return Math.max(0, Math.min(100, score));
}

/** 점수 → severity. <20 INFO / <40 WATCH / <65 WARN / ≥65 URGENT. */
function severityFromScore(score: number): Severity
{
    if (score >= 65)
    {
        return "URGENT";
    }

    if (score >= 40)
    {
        return "WARN";
    }

    if (score >= 20)
    {
        return "WATCH";
    }

    return "INFO";
}

const SEVERITY_RANK: Record<Severity, number> = { INFO: 0, WATCH: 1, WARN: 2, URGENT: 3 };

/** severity 를 cap 이하로 제한. */
function capSeverity(severity: Severity, cap: Severity): Severity
{
    return SEVERITY_RANK[severity] > SEVERITY_RANK[cap] ? cap : severity;
}

/** approval severity. MALICIOUS 단독은 점수와 무관하게 URGENT. VERIFIED 무제한은 상한 WATCH. */
export function classifyApprovalSeverity(input: ApprovalInput): Severity
{
    if (input.spenderReputation === "MALICIOUS")
    {
        return "URGENT";
    }

    const severity = severityFromScore(scoreApprovalRisk(input));

    if (input.spenderReputation === "VERIFIED")
    {
        // 신뢰 라우터는 알림 피로 방지 위해 상한 WATCH(무제한 사실은 facts 에 유지).
        return capSeverity(severity, "WATCH");
    }

    return severity;
}
