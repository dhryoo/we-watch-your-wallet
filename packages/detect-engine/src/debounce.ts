/**
 * 공통 디바운스 상태기계 — 순수 reducer. architecture.md §2.3.
 * RAW → PENDING(firstSeen) → (N_confirm 연속) → CONFIRMED → (M_clear 연속 해제) → RESOLVED.
 * escalateBypass: severity 한 단계↑ 악화 시 즉시 CONFIRMED. cooldown: 동일 subjectKey·severity 재알림 최소 간격.
 */
import type { Severity } from "./types";

export type DebounceStatus = "PENDING" | "CONFIRMED" | "RESOLVED";

export interface DebounceConfig
{
    nConfirm: number;
    mClear: number;
    cooldownMs: number;
}

export interface DebounceInput
{
    triggered: boolean;
    severity: Severity;
    ts: number;
    escalated?: boolean;
}

export interface DebounceState
{
    status: DebounceStatus;
    firstSeen: number;
    consecutiveTriggers: number;
    consecutiveClears: number;
    severity: Severity;
    lastAlertAt: number | null;
    lastAlertSeverity: Severity | null;
    shouldAlert: boolean;
}

function initialState(severity: Severity): DebounceState
{
    return {
        status: "RESOLVED",
        firstSeen: 0,
        consecutiveTriggers: 0,
        consecutiveClears: 0,
        severity,
        lastAlertAt: null,
        lastAlertSeverity: null,
        shouldAlert: false
    };
}

export function stepDebounce(prev: DebounceState | null, input: DebounceInput, config: DebounceConfig): DebounceState
{
    const base = prev ?? initialState(input.severity);

    // escalateBypass: severity 한 단계↑ 악화 시 디바운스/쿨다운 무시하고 즉시 CONFIRMED + 알림.
    if (input.escalated === true)
    {
        const active = base.status !== "RESOLVED";

        return {
            status: "CONFIRMED",
            firstSeen: active && base.firstSeen !== 0 ? base.firstSeen : input.ts,
            consecutiveTriggers: base.consecutiveTriggers + 1,
            consecutiveClears: 0,
            severity: input.severity,
            lastAlertAt: input.ts,
            lastAlertSeverity: input.severity,
            shouldAlert: true
        };
    }

    if (input.triggered)
    {
        const fresh = base.status === "RESOLVED";
        const consecutiveTriggers = fresh ? 1 : base.consecutiveTriggers + 1;
        const firstSeen = fresh || base.firstSeen === 0 ? input.ts : base.firstSeen;
        const reachedConfirm = consecutiveTriggers >= config.nConfirm;
        const newlyConfirmed = reachedConfirm && base.status !== "CONFIRMED";

        // cooldown: 동일 severity 알림이 cooldownMs 내에 있었으면 재알림 억제.
        const suppressed = base.lastAlertAt !== null
            && input.ts - base.lastAlertAt < config.cooldownMs
            && base.lastAlertSeverity === input.severity;
        const shouldAlert = newlyConfirmed && !suppressed;

        return {
            status: reachedConfirm ? "CONFIRMED" : "PENDING",
            firstSeen,
            consecutiveTriggers,
            consecutiveClears: 0,
            severity: input.severity,
            lastAlertAt: shouldAlert ? input.ts : base.lastAlertAt,
            lastAlertSeverity: shouldAlert ? input.severity : base.lastAlertSeverity,
            shouldAlert
        };
    }

    // 해제(cleared): 연속 해제 누적, mClear 도달 시 RESOLVED.
    const consecutiveClears = base.consecutiveClears + 1;
    const active = base.status === "CONFIRMED" || base.status === "PENDING";
    const status: DebounceStatus = active && consecutiveClears >= config.mClear ? "RESOLVED" : base.status;

    return {
        status,
        firstSeen: base.firstSeen,
        consecutiveTriggers: 0,
        consecutiveClears,
        severity: input.severity,
        lastAlertAt: base.lastAlertAt,
        lastAlertSeverity: base.lastAlertSeverity,
        shouldAlert: false
    };
}
