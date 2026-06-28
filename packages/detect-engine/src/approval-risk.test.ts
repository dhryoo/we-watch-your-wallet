import { describe, expect, it } from "vitest";
import {
    buildRevokeLink,
    classifyApprovalSeverity,
    isUnlimitedAllowance,
    scoreApprovalRisk
} from "./approval-risk";

describe("approval-risk: isUnlimitedAllowance", () =>
{
    it("shouldDetectUnlimitedAllowance", () =>
    {
        // 무제한 기준: allowance ≥ 2^256/2 (= 2^255).
        expect(isUnlimitedAllowance(2n ** 255n)).toBe(true);
        expect(isUnlimitedAllowance(2n ** 256n - 1n)).toBe(true); // max uint256 (전형적 무제한)
        expect(isUnlimitedAllowance(2n ** 255n - 1n)).toBe(false);
        expect(isUnlimitedAllowance(1000n)).toBe(false);
    });
});

describe("approval-risk: scoreApprovalRisk", () =>
{
    it("shouldScoreMaliciousSpenderHighest", () =>
    {
        // MALICIOUS 스펜더 = 최고 가중치 +60.
        expect(scoreApprovalRisk({ spenderReputation: "MALICIOUS" })).toBe(60);
    });

    it("shouldScoreEoaSpender", () =>
    {
        // 스펜더가 EOA(컨트랙트 아님) → +30.
        expect(scoreApprovalRisk({ spenderType: "EOA" })).toBe(30);
    });

    it("shouldScoreUnverifiedSpender", () =>
    {
        // 미검증(소스 미공개) 컨트랙트 → +25.
        expect(scoreApprovalRisk({ spenderReputation: "UNVERIFIED" })).toBe(25);
    });

    it("shouldAccumulateApprovalSignals", () =>
    {
        // 여러 신호 가산 합산.
        expect(scoreApprovalRisk({
            spenderType: "EOA",              // +30
            spenderReputation: "UNVERIFIED", // +25
            recentlyGranted: true            // +10
        })).toBe(65);

        // 합이 100 초과 → 상한 100 clamp.
        expect(scoreApprovalRisk({
            spenderReputation: "MALICIOUS",  // +60
            spenderType: "EOA",              // +30
            allowance: 2n ** 256n - 1n,      // unlimited +20
            isSetApprovalForAll: true        // +20  (합 130)
        })).toBe(100);
    });
});

describe("approval-risk: classifyApprovalSeverity", () =>
{
    it("shouldClassifyMaliciousApprovalUrgent", () =>
    {
        // MALICIOUS 단독은 점수(60<65)와 무관하게 URGENT.
        expect(classifyApprovalSeverity({ spenderReputation: "MALICIOUS" })).toBe("URGENT");
    });

    it("shouldCapVerifiedRouterAtWatch", () =>
    {
        // VERIFIED 라우터: 점수상 WARN(45)이라도 피로 방지 위해 severity 상한 WATCH.
        const verified = {
            spenderReputation: "VERIFIED" as const,
            allowance: 2n ** 256n - 1n,   // 무제한 +20
            highValueToken: true,          // +10
            balanceMultipleOver50x: true   // +15  (점수 45 → 미적용 시 WARN)
        };

        expect(["INFO", "WATCH"]).toContain(classifyApprovalSeverity(verified));
        // "무제한" 사실 자체는 facts 에서 표기 유지.
        expect(isUnlimitedAllowance(verified.allowance)).toBe(true);
    });
});

describe("approval-risk: buildRevokeLink", () =>
{
    it("shouldBuildRevokeCashDeeplink", () =>
    {
        // 표시만(실행은 사용자 본인 지갑). architecture.md §2.2.
        expect(buildRevokeLink("0xAbC123", 8453))
            .toBe("https://revoke.cash/address/0xAbC123?chainId=8453");
    });
});
