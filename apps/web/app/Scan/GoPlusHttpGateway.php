<?php

namespace App\Scan;

use Illuminate\Support\Facades\Http;

/**
 * GoPlus Approval Security API (키 불필요, 30/min). architecture.md §4.
 */
class GoPlusHttpGateway implements GoPlusGateway
{
    private const BASE = 'https://api.gopluslabs.io/api/v2/token_approval_security';

    public function approvals(string $address, int $chainId): array
    {
        try
        {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get(self::BASE . "/{$chainId}", ['addresses' => $address]);
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

        if (($data['code'] ?? 0) !== 1)
        {
            throw new GoPlusException('GoPlus code ' . (string) ($data['code'] ?? 'null'));
        }

        return is_array($data['result'] ?? null) ? $data['result'] : [];
    }
}
