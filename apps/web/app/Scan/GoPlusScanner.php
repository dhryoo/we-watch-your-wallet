<?php

namespace App\Scan;

/**
 * GoPlus 응답 → scanResult(결정론적 스코어 + 평문 설명). 자문 아님.
 * 정직성: GoPlus 실패 시 healthy 로 위장하지 않고 failed/stale 표기. architecture.md §2.2/§4.
 * 평문 설명: RiskExplainer(있으면 Claude)로 생성하되 신뢰 불가/미설정 시 결정론적 템플릿으로 폴백.
 * 비용/지연: LLM은 신선 스캔의 "최상위 maxLlmExplanations건"(기본 1)에만 호출 → 위험 수 N이 아니라 상수 K로 묶임.
 * + 24h 캐시·일일한도(4중 비용격리). 온체인 토큰 심볼은 공격자 제어 데이터라 cleanSymbol로 sanitize.
 */
class GoPlusScanner
{
    private const RANK = ['INFO' => 0, 'WATCH' => 1, 'WARN' => 2, 'URGENT' => 3];
    private const SEV = ['INFO', 'WATCH', 'WARN', 'URGENT'];
    private const RECENT_WINDOW = 2592000; // 30d

    public function __construct(
        private readonly GoPlusGateway $gateway,
        private readonly ?RiskExplainer $explainer = null,
        private readonly int $maxLlmExplanations = 1,
    ) {
    }

    /** @return array<string,mixed> */
    public function scan(string $address, int $chainId, ?int $now = null): array
    {
        $now ??= time();

        try
        {
            $tokens = $this->gateway->approvals($address, $chainId);
        }
        catch (\Throwable $e)
        {
            return $this->staleResult();
        }

        $revokeUrl = "https://revoke.cash/address/{$address}?chainId={$chainId}";
        $reviewed = 0;
        $maxScore = 0;
        $topRank = 0;
        $risks = [];

        foreach ($tokens as $token)
        {
            $symbol = $this->cleanSymbol($token['token_symbol'] ?? null);
            $tokenAddr = Address::short((string) ($token['token_address'] ?? ''));
            $balance = (float) ($token['balance'] ?? 0);

            foreach (($token['approved_list'] ?? []) as $spender)
            {
                $reviewed++;
                $input = $this->mapInput($spender, $balance, $now);
                $score = ApprovalRisk::score($input);
                $severity = ApprovalRisk::classify($input);
                $maxScore = max($maxScore, $score);
                $topRank = max($topRank, self::RANK[$severity]);

                if ($severity === 'INFO')
                {
                    continue;
                }

                $risks[] = $this->riskItem($symbol, $tokenAddr, $input, $severity, $revokeUrl);
            }
        }

        $risks = ScanResult::sortBySeverityDesc($risks);
        $risks = $this->enrichExplanations($risks);

        return [
            'score' => $maxScore,
            'severity' => $risks === [] ? 'INFO' : self::SEV[$topRank],
            'approvalsReviewed' => $reviewed,
            'stale' => false,
            'failed' => false,
            'risks' => $risks,
        ];
    }

    /** @return array<string,mixed> */
    private function staleResult(): array
    {
        return [
            'score' => 0,
            'severity' => 'WATCH', // 비-INFO: 실패를 정상으로 간주 금지
            'approvalsReviewed' => 0,
            'stale' => true,
            'failed' => true,
            'risks' => [],
        ];
    }

    /** @return array<string,mixed> */
    private function mapInput(array $spender, float $balance, int $now): array
    {
        $info = $spender['address_info'] ?? [];
        $amountRaw = (string) ($spender['approved_amount'] ?? '');
        $unlimited = ApprovalRisk::isUnlimited($amountRaw);
        $amount = is_numeric($amountRaw) ? (float) $amountRaw : 0.0;
        $grantedAt = $spender['approved_time'] ?? $spender['initial_approval_time'] ?? null;

        return [
            'spenderReputation' => $this->reputation($info),
            'spenderType' => ((int) ($info['is_contract'] ?? 1) === 0) ? 'EOA' : 'CONTRACT',
            'unlimited' => $unlimited,
            'isSetApprovalForAll' => false,
            'balanceMultipleOver50x' => !$unlimited && $balance > 0 && $amount >= 50 * $balance,
            'highValueToken' => false,
            'recentlyGranted' => is_numeric($grantedAt) && (int) $grantedAt > 0 && ($now - (int) $grantedAt) < self::RECENT_WINDOW,
            'amount' => $amount,
        ];
    }

    private function reputation(array $info): ?string
    {
        if (!empty($info['malicious_behavior']) || (int) ($info['doubt_list'] ?? 0) === 1)
        {
            return 'MALICIOUS';
        }

        if ((int) ($info['trust_list'] ?? 0) === 1)
        {
            return 'VERIFIED';
        }

        if ((int) ($info['is_open_source'] ?? 1) === 0)
        {
            return 'UNVERIFIED';
        }

        return null;
    }

    /** @return array<string,mixed> */
    private function riskItem(string $symbol, string $tokenAddr, array $input, string $severity, string $revokeUrl): array
    {
        $reputation = $input['spenderReputation'];
        $signals = $this->signals($input);
        // 헤드라인은 템플릿으로 먼저 채우고, 정렬 후 최상위 K건만 enrichExplanations에서 LLM으로 업그레이드.
        [$explain, $why, $checklist] = $this->template($reputation, $input['unlimited'], $symbol);

        return [
            'token' => $symbol,
            'tokenAddress' => $tokenAddr,
            'tokenInit' => strtoupper(substr($symbol, 0, 1)) ?: '?',
            'spenderTag' => $reputation ?? 'VERIFIED',
            'isContract' => $input['spenderType'] === 'CONTRACT',
            'unlimited' => $input['unlimited'],
            'limitText' => $input['unlimited'] ? null : $this->formatAmount($input['amount'], $symbol),
            'stale' => false,
            'signals' => $signals,
            'explain' => $explain,
            'why' => $why,
            'checklist' => $checklist,
            'severity' => $severity,
            'revokeUrl' => $revokeUrl,
        ];
    }

    /**
     * 온체인 토큰 심볼(공격자 제어 가능)을 안전 charset·길이로 정제.
     * letters/numbers/space/_/- 만 남기고 16자 cap → 프롬프트 인젝션·URL·개행·UI 깨짐 차단.
     */
    private function cleanSymbol(mixed $raw): string
    {
        $stripped = preg_replace('/[^\p{L}\p{N} _-]/u', '', (string) $raw) ?? '';
        $collapsed = trim((string) preg_replace('/\s+/u', ' ', $stripped));
        $capped = mb_substr($collapsed, 0, 16);

        return $capped !== '' ? $capped : '?';
    }

    /**
     * 정렬된 위험 목록의 "최상위 maxLlmExplanations건"만 LLM 헤드라인 설명으로 업그레이드.
     * 나머지·실패·미설정은 템플릿 유지 → 비용/지연을 N(위험 수)이 아니라 상수 K로 묶는다.
     * why/checklist는 항상 안전한 템플릿(LLM은 헤드라인 한 문장만 대체).
     *
     * @param array<int,array<string,mixed>> $risks
     * @return array<int,array<string,mixed>>
     */
    private function enrichExplanations(array $risks): array
    {
        if ($this->explainer === null || $this->maxLlmExplanations < 1)
        {
            return $risks;
        }

        $cap = min($this->maxLlmExplanations, count($risks));

        for ($i = 0; $i < $cap; $i++)
        {
            $risk = $risks[$i];

            try
            {
                $llm = $this->explainer->explain([
                    'token' => $risk['token'],
                    'spenderReputation' => $risk['spenderTag'],
                    'spenderType' => $risk['isContract'] ? 'CONTRACT' : 'EOA',
                    'unlimited' => $risk['unlimited'],
                    'signals' => $risk['signals'],
                    'severity' => $risk['severity'],
                ]);
            }
            catch (\Throwable $e)
            {
                $llm = null; // 설명 실패가 스캔을 죽이면 안 된다 — 템플릿 유지
            }

            if ($llm !== null && $llm !== '')
            {
                $risks[$i]['explain'] = $llm;
            }
        }

        return $risks;
    }

    /** @return array<int,string> */
    private function signals(array $input): array
    {
        $signals = [];

        if ($input['unlimited'])
        {
            $signals[] = 'Unlimited approval';
        }

        if ($input['spenderReputation'] === 'MALICIOUS')
        {
            $signals[] = 'Malicious spender';
        }

        if ($input['spenderReputation'] === 'UNVERIFIED')
        {
            $signals[] = 'Unverified contract';
        }

        if ($input['spenderType'] === 'EOA')
        {
            $signals[] = 'EOA spender';
        }

        if ($input['balanceMultipleOver50x'])
        {
            $signals[] = 'Approval far exceeds balance';
        }

        if ($input['recentlyGranted'])
        {
            $signals[] = 'Recently granted';
        }

        return $signals === [] ? ['Review recommended'] : $signals;
    }

    /** @return array{0:string,1:string,2:array<int,string>} */
    private function template(?string $reputation, bool $unlimited, string $symbol): array
    {
        $allowance = $unlimited ? 'an unlimited' : 'a';
        $exposure = $unlimited ? 'your entire balance' : 'the approved amount';

        if ($reputation === 'MALICIOUS')
        {
            return [
                "A spender flagged as malicious holds {$allowance} approval on your {$symbol}, letting it move {$exposure} at any time.",
                'Malicious contracts rely on approvals to drain a token once you have granted them.',
                ['Confirm you recognize this spender', "Revoke it if you don't use it"],
            ];
        }

        if ($reputation === 'UNVERIFIED')
        {
            return [
                "An unverified contract holds {$allowance} approval on your {$symbol}. Unverified source code cannot be independently reviewed.",
                'Without published source code, you cannot tell what the contract will do with the approval you granted.',
                ['Check the contract on a block explorer', 'Revoke if you no longer use it'],
            ];
        }

        if ($unlimited)
        {
            return [
                "This spender holds an unlimited approval on your {$symbol}, so it can move your full balance.",
                'Unlimited approvals you no longer use stay active; capping or revoking limits your exposure.',
                ['Confirm you still use this app', 'Revoke or reduce the allowance if unused'],
            ];
        }

        return [
            "A capped approval on your {$symbol}. Exposure is limited to the approved amount.",
            'Old approvals you no longer use stay active and are worth cleaning up, even when capped.',
            ['Confirm you still use this app', 'Revoke if unused'],
        ];
    }

    private function formatAmount(float $amount, string $symbol): string
    {
        $rounded = $amount >= 1 ? number_format($amount) : rtrim(rtrim(number_format($amount, 6, '.', ''), '0'), '.');

        return "{$rounded} {$symbol}";
    }
}
