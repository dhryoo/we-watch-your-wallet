import { describe, expect, it } from "vitest";
import { DETECTION_ACTIONS, type DetectionAction, type DetectionEvent } from "./types";

describe("types: 비자문 불변식", () =>
{
    it("shouldOnlyExposeReviewOrInformAction", () =>
    {
        // 런타임: 노출 가능한 action 은 REVIEW/INFORM 뿐.
        expect([...DETECTION_ACTIONS].sort()).toEqual(["INFORM", "REVIEW"]);

        for (const forbidden of ["BUY", "SELL", "SWAP", "CLOSE"])
        {
            expect(DETECTION_ACTIONS as readonly string[]).not.toContain(forbidden);
        }

        // 컴파일타임 가드: 매매권유 값은 DetectionAction 에 부재해야 한다.
        // @ts-expect-error — "SELL" 은 DetectionAction 타입에 없음(이 라인이 에러 안 나면 typecheck 실패).
        const forbiddenAction: DetectionAction = "SELL";
        void forbiddenAction;

        const ok: DetectionEvent["action"] = "REVIEW";
        expect(ok).toBe("REVIEW");
    });
});
