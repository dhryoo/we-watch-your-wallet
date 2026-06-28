<?php

namespace App\Scan;

use Illuminate\Support\Facades\Http;

/**
 * GoPlus Approval Security API (키 불필요, 30/min). architecture.md §4.
 * NFT는 ERC721/ERC1155 엔드포인트가 분리돼 있어 둘을 합친다(하나가 실패해도 나머지는 사용).
 * code 1=complete, 2=partial(스캐닝 중) 둘 다 데이터 사용.
 */
class GoPlusHttpGateway implements GoPlusGateway
{
    private const TOKEN_BASE = 'https://api.gopluslabs.io/api/v2/token_approval_security';
    private const NFT721_BASE = 'https://api.gopluslabs.io/api/v2/nft721_approval_security';
    private const NFT1155_BASE = 'https://api.gopluslabs.io/api/v2/nft1155_approval_security';

    /** code 2(부분) 또는 NFT 한쪽 엔드포인트 실패 누적 — 결과를 STALE로 표기하기 위함. */
    private bool $partial = false;

    public function wasPartial(): bool
    {
        return $this->partial;
    }

    public function approvals(string $address, int $chainId): array
    {
        return $this->fetch(self::TOKEN_BASE, $address, $chainId);
    }

    public function nftApprovals(string $address, int $chainId): array
    {
        $merged = [];
        $failures = 0;

        foreach ([self::NFT721_BASE, self::NFT1155_BASE] as $base)
        {
            try
            {
                $merged = array_merge($merged, $this->fetch($base, $address, $chainId));
            }
            catch (\Throwable $e)
            {
                $failures++;
            }
        }

        if ($failures === 2)
        {
            throw new GoPlusException('GoPlus NFT endpoints failed');
        }

        if ($failures === 1)
        {
            $this->partial = true; // 한쪽만 받았으니 불완전 → STALE
        }

        return $merged;
    }

    /** @return array<int,array<string,mixed>> */
    private function fetch(string $base, string $address, int $chainId): array
    {
        try
        {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get("{$base}/{$chainId}", ['addresses' => $address]);
        }
        catch (\Throwable $e)
        {
            throw new GoPlusException('GoPlus request failed: ' . $e->getMessage(), 0, $e);
        }

        if (!$response->successful())
        {
            throw new GoPlusException("GoPlus HTTP {$response->status()}");
        }

        $data = $response->json();

        $code = $data['code'] ?? 0;

        // 1=complete, 2=partial(아직 스캐닝) — 둘 다 result 데이터를 쓰되 2는 부분으로 표기(거짓 clean 방지).
        if (!in_array($code, [1, 2], true))
        {
            throw new GoPlusException('GoPlus code ' . (string) ($code === 0 ? 'null' : $code));
        }

        if ($code === 2)
        {
            $this->partial = true;
        }

        return is_array($data['result'] ?? null) ? $data['result'] : [];
    }
}
