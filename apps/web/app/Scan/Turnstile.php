<?php

namespace App\Scan;

use Illuminate\Support\Facades\Http;

/**
 * 4중 비용격리 #1 — Cloudflare Turnstile 캡차 검증(봇 차단).
 * 시크릿 미설정(개발/1주차)이면 우회(통과). 운영은 TURNSTILE_SECRET 설정.
 */
class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(private readonly ?string $secret)
    {
    }

    public function configured(): bool
    {
        return $this->secret !== null && $this->secret !== '';
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (!$this->configured())
        {
            return true; // 우회
        }

        if ($token === null || $token === '')
        {
            return false;
        }

        try
        {
            $response = Http::asForm()->timeout(10)->post(self::VERIFY_URL, array_filter([
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $ip,
            ]));
        }
        catch (\Throwable $e)
        {
            return false;
        }

        return (bool) ($response->json('success') ?? false);
    }
}
