<?php

namespace App\Scan;

/**
 * 위험 Approval 결정론적 가중합(자문 아님). architecture.md §2.2 / detect-engine(TS) 와 동일 스펙.
 * $input 키: spenderReputation('MALICIOUS'|'VERIFIED'|'UNVERIFIED'|null), spenderType('EOA'|'CONTRACT'),
 *   unlimited, isSetApprovalForAll, balanceMultipleOver50x, highValueToken, recentlyGranted (bool).
 */
class ApprovalRisk
{
    private const RANK = ['INFO' => 0, 'WATCH' => 1, 'WARN' => 2, 'URGENT' => 3];

    /** @param array<string,mixed> $input */
    public static function score(array $input): int
    {
        $score = 0;

        if (($input['spenderReputation'] ?? null) === 'MALICIOUS')
        {
            $score += 60;
        }

        if (($input['spenderType'] ?? null) === 'EOA')
        {
            $score += 30;
        }

        if (($input['spenderReputation'] ?? null) === 'UNVERIFIED')
        {
            $score += 25;
        }

        if ($input['unlimited'] ?? false)
        {
            $score += 20;
        }

        if ($input['isSetApprovalForAll'] ?? false)
        {
            $score += 20;
        }

        if ($input['balanceMultipleOver50x'] ?? false)
        {
            $score += 15;
        }

        if ($input['highValueToken'] ?? false)
        {
            $score += 10;
        }

        if ($input['recentlyGranted'] ?? false)
        {
            $score += 10;
        }

        return max(0, min(100, $score));
    }

    /** @param array<string,mixed> $input */
    public static function classify(array $input): string
    {
        // MALICIOUS 단독은 점수와 무관하게 URGENT.
        if (($input['spenderReputation'] ?? null) === 'MALICIOUS')
        {
            return 'URGENT';
        }

        $severity = self::fromScore(self::score($input));

        // VERIFIED 라우터는 알림 피로 방지 위해 상한 WATCH.
        if (($input['spenderReputation'] ?? null) === 'VERIFIED' && self::RANK[$severity] > self::RANK['WATCH'])
        {
            return 'WATCH';
        }

        return $severity;
    }

    private static function fromScore(int $score): string
    {
        if ($score >= 65)
        {
            return 'URGENT';
        }

        if ($score >= 40)
        {
            return 'WARN';
        }

        if ($score >= 20)
        {
            return 'WATCH';
        }

        return 'INFO';
    }

    /** GoPlus approved_amount(평문 십진수 또는 'unlimited') → 무제한 여부. MaxUint 계열 큰 값 포함. */
    public static function isUnlimited(string $approvedAmount): bool
    {
        $value = strtolower(trim($approvedAmount));

        if ($value === '' || $value === 'unlimited')
        {
            return true;
        }

        if (!is_numeric($value))
        {
            return false;
        }

        return (float) $value >= 1e30;
    }
}
