<?php

namespace App\Scan;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 스캔 오케스트레이션 + 4중 비용격리 #2(IP·지갑 일일한도) #3(주소별 24h 캐시).
 * 캐시 히트는 무료(한도 미차감). 미스만 한도 검사 후 신선 GoPlus 스캔.
 */
class ScanService
{
    public function __construct(private readonly GoPlusScanner $scanner)
    {
    }

    public function cacheKey(string $address, int $chainId): string
    {
        return "scan:{$chainId}:" . strtolower($address);
    }

    /** @return array<string,mixed>|null 캐시 히트 또는 신선 스캔 결과. 한도 초과 시 null. */
    public function scan(string $address, int $chainId, ?string $ip = null): ?array
    {
        $cached = Cache::get($this->cacheKey($address, $chainId));

        if ($cached !== null)
        {
            return $cached;
        }

        if (!$this->withinRateLimit($address, $ip))
        {
            return null;
        }

        $result = $this->scanner->scan($address, $chainId);

        // 실패(STALE)는 캐시하지 않아 다음 시도에서 재시도 가능.
        if (($result['failed'] ?? false) === false)
        {
            Cache::put($this->cacheKey($address, $chainId), $result, (int) config('scan.cache_ttl', 86400));
        }

        return $result;
    }

    private function withinRateLimit(string $address, ?string $ip): bool
    {
        $ipKey = 'scan-ip:' . ($ip ?? 'unknown');
        $walletKey = 'scan-wallet:' . strtolower($address);
        $ipMax = (int) config('scan.ip_daily_limit', 30);
        $walletMax = (int) config('scan.wallet_daily_limit', 5);

        if (RateLimiter::tooManyAttempts($ipKey, $ipMax) || RateLimiter::tooManyAttempts($walletKey, $walletMax))
        {
            return false;
        }

        RateLimiter::hit($ipKey, 86400);
        RateLimiter::hit($walletKey, 86400);

        return true;
    }
}
