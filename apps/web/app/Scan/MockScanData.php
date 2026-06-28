<?php

namespace App\Scan;

/**
 * Phase 3-UI 목업 데이터. README 의 scanResult/RiskApproval 형태.
 * 실제 GoPlus 스캔·4중 비용격리 연결은 이후 별도 작업에서 대체한다.
 */
class MockScanData
{
    public static function revokeUrl(string $address, int $chainId): string
    {
        return "https://revoke.cash/address/{$address}?chainId={$chainId}";
    }

    /** @return array<string,mixed> */
    public static function risky(string $address, int $chainId = 1): array
    {
        $revoke = self::revokeUrl($address, $chainId);

        return [
            'score' => 82,
            'severity' => 'URGENT',
            'approvalsReviewed' => 47,
            'stale' => true,
            'risks' => [
                [
                    'token' => 'USDC',
                    'tokenAddress' => '0xA0b8…48Ce',
                    'tokenInit' => 'U',
                    'spenderTag' => 'MALICIOUS',
                    'isContract' => true,
                    'unlimited' => true,
                    'limitText' => null,
                    'stale' => false,
                    'signals' => ['Unlimited approval', 'Malicious spender'],
                    'explain' => 'A spender flagged as malicious holds an unlimited approval on your USDC, letting it move your entire balance at any time.',
                    'why' => 'Malicious contracts rely on unlimited approvals to drain a token in a single transaction once you have approved them.',
                    'checklist' => ['Confirm you recognize this spender', "Revoke it if you don't use it"],
                    'severity' => 'URGENT',
                    'revokeUrl' => $revoke,
                ],
                [
                    'token' => 'WETH',
                    'tokenAddress' => '0xC02a…6Cc2',
                    'tokenInit' => 'W',
                    'spenderTag' => 'UNVERIFIED',
                    'isContract' => true,
                    'unlimited' => true,
                    'limitText' => null,
                    'stale' => true,
                    'signals' => ['Unlimited approval', 'Unverified contract'],
                    'explain' => 'An unverified contract holds an unlimited approval on your WETH. Unverified source code cannot be independently reviewed.',
                    'why' => 'Without published source code, you cannot tell what the contract will do with the approval you granted.',
                    'checklist' => ['Check the contract on a block explorer', 'Revoke if you no longer use it'],
                    'severity' => 'WARN',
                    'revokeUrl' => $revoke,
                ],
                [
                    'token' => 'DAI',
                    'tokenAddress' => '0x6B17…1d0F',
                    'tokenInit' => 'D',
                    'spenderTag' => 'VERIFIED',
                    'isContract' => true,
                    'unlimited' => false,
                    'limitText' => '1,000 DAI',
                    'stale' => false,
                    'signals' => ['Approval older than 6 months'],
                    'explain' => 'A verified router holds a capped DAI approval granted some time ago. The amount is limited, so exposure is bounded.',
                    'why' => 'Old approvals you no longer use stay active and are worth cleaning up, even when capped.',
                    'checklist' => ['Confirm you still use this app', 'Revoke if unused'],
                    'severity' => 'WATCH',
                    'revokeUrl' => $revoke,
                ],
            ],
        ];
    }

    /** @return array<string,mixed> */
    public static function safe(string $address, int $chainId = 1): array
    {
        return [
            'score' => 8,
            'severity' => 'INFO',
            'approvalsReviewed' => 12,
            'stale' => false,
            'risks' => [],
        ];
    }
}
