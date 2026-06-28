import { describe, expect, it } from "vitest";
import { stepDebounce, type DebounceConfig } from "./debounce";

const CONFIG: DebounceConfig = { nConfirm: 3, mClear: 2, cooldownMs: 6 * 60 * 60 * 1000 };

describe("debounce: stepDebounce", () =>
{
    it("shouldStartPendingOnFirstTrigger", () =>
    {
        const state = stepDebounce(null, { triggered: true, severity: "WARN", ts: 1000 }, CONFIG);

        expect(state.status).toBe("PENDING");
        expect(state.firstSeen).toBe(1000);
    });

    it("shouldConfirmAfterNConsecutive", () =>
    {
        // nConfirm=3: 3회 연속 트리거 유지 시 CONFIRMED + 알림.
        let state = stepDebounce(null, { triggered: true, severity: "WARN", ts: 1000 }, CONFIG);
        state = stepDebounce(state, { triggered: true, severity: "WARN", ts: 2000 }, CONFIG);
        expect(state.status).toBe("PENDING"); // 2/3

        state = stepDebounce(state, { triggered: true, severity: "WARN", ts: 3000 }, CONFIG);
        expect(state.status).toBe("CONFIRMED"); // 3/3
        expect(state.shouldAlert).toBe(true);
    });

    it("shouldResolveAfterMClear", () =>
    {
        let state = stepDebounce(null, { triggered: true, severity: "WARN", ts: 1000 }, CONFIG);
        state = stepDebounce(state, { triggered: true, severity: "WARN", ts: 2000 }, CONFIG);
        state = stepDebounce(state, { triggered: true, severity: "WARN", ts: 3000 }, CONFIG);
        expect(state.status).toBe("CONFIRMED");

        // mClear=2: 연속 2회 해제 시 RESOLVED.
        state = stepDebounce(state, { triggered: false, severity: "WARN", ts: 4000 }, CONFIG);
        expect(state.status).toBe("CONFIRMED"); // 1/2 해제
        state = stepDebounce(state, { triggered: false, severity: "WARN", ts: 5000 }, CONFIG);
        expect(state.status).toBe("RESOLVED"); // 2/2 해제
    });

    it("shouldSuppressWithinCooldown", () =>
    {
        const cfg: DebounceConfig = { nConfirm: 1, mClear: 1, cooldownMs: 6 * 60 * 60 * 1000 };

        const a = stepDebounce(null, { triggered: true, severity: "WARN", ts: 0 }, cfg);
        expect(a.status).toBe("CONFIRMED");
        expect(a.shouldAlert).toBe(true); // 최초 알림

        const cleared = stepDebounce(a, { triggered: false, severity: "WARN", ts: 1000 }, cfg);
        expect(cleared.status).toBe("RESOLVED");

        // 6h 내 동일 severity 재확정 → 재알림 억제(0).
        const b = stepDebounce(cleared, { triggered: true, severity: "WARN", ts: 2000 }, cfg);
        expect(b.status).toBe("CONFIRMED");
        expect(b.shouldAlert).toBe(false);
    });

    it("shouldBypassDebounceOnEscalation", () =>
    {
        // nConfirm=3 이라도 severity 한 단계↑ 악화면 단 1회로 즉시 CONFIRMED + 알림.
        const state = stepDebounce(
            null,
            { triggered: true, severity: "URGENT", ts: 100, escalated: true },
            CONFIG
        );

        expect(state.status).toBe("CONFIRMED");
        expect(state.shouldAlert).toBe(true);
    });
});
