<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Scan\Chain;
use App\Scan\ScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public, read-only JSON scan API — an embeddable distribution surface.
 * No auth. Reuses ScanService (24h cache + per-IP/-wallet daily limits) so cost isolation
 * is the same as the web scan. CORS open: this is public, read-only on-chain data.
 * Honesty: upstream failure returns 200 with stale/failed flags, never a fake "safe".
 */
class ScanApiController extends Controller
{
    private const ADDRESS_RE = '/^0x[0-9a-fA-F]{40}$/';

    public function __construct(private readonly ScanService $scans)
    {
    }

    public function show(Request $request): JsonResponse
    {
        // 라우트 파라미터는 이름으로 읽는다(체인 유무 두 라우트를 한 메서드로).
        $address = (string) $request->route('address');
        $chain = (string) $request->route('chain', Chain::DEFAULT);
        $chainMeta = Chain::fromSlug($chain);

        if ($chainMeta === null)
        {
            return $this->json(['error' => 'unsupported_chain', 'message' => 'Supported chains: ' . implode(', ', Chain::slugs()) . '.'], 422);
        }

        if (!preg_match(self::ADDRESS_RE, $address))
        {
            return $this->json(['error' => 'invalid_address', 'message' => 'Provide a 0x-prefixed, 40-hex Ethereum address.'], 422);
        }

        $chainId = $chainMeta['id'];
        $result = $this->scans->scan($address, $chainId, $request->ip());

        if ($result === null)
        {
            return $this->json(['error' => 'rate_limited', 'message' => 'Daily scan limit reached. Please try again tomorrow.'], 429);
        }

        // 정직성: 업스트림 실패는 깨끗한 지갑처럼 보이는 200(score:0/risks:[])이 아니라 명시적 503으로.
        if ($result['failed'] ?? false)
        {
            return $this->json([
                'error' => 'scan_unavailable',
                'stale' => true,
                'failed' => true,
                'message' => 'Risk data was unavailable upstream — this is NOT a clean-wallet result. Please try again shortly.',
            ], 503);
        }

        return $this->json([
            'address' => $address,
            'chainId' => $chainId,
            'scannedAt' => gmdate('Y-m-d\TH:i:s\Z', (int) ($result['scannedAt'] ?? time())),
            'score' => $result['score'],
            'severity' => $result['severity'],
            'approvalsReviewed' => $result['approvalsReviewed'],
            'stale' => (bool) ($result['stale'] ?? false),
            'failed' => (bool) ($result['failed'] ?? false),
            'risks' => array_map(static fn (array $r): array => [
                'token' => $r['token'],
                'tokenAddress' => $r['tokenAddress'],
                'spender' => $r['spenderTag'],
                'spenderAddress' => $r['spenderAddress'] ?? null,
                'severity' => $r['severity'],
                'unlimited' => (bool) $r['unlimited'],
                'limit' => $r['limitText'],
                'explanation' => $r['explain'],
                'revokeUrl' => $r['revokeUrl'],
            ], $result['risks']),
            'disclaimer' => 'Informational only — not financial, investment, or legal advice. Read-only and non-custodial.',
        ], 200);
    }

    /** @param array<string,mixed> $data */
    private function json(array $data, int $status): JsonResponse
    {
        return response()->json($data, $status, ['Access-Control-Allow-Origin' => '*'], JSON_UNESCAPED_SLASHES);
    }
}
