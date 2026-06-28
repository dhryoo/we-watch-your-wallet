<?php

namespace App\Scan;

/**
 * 지갑 주소 표시 변형. 기본은 마스킹(개인정보·OG 카드 민감정보 노출 방지).
 */
class Address
{
    /** 헤더용 마스킹: 0x + 첫 4 hex + •••••• + 끝 4 hex (예: 0x7a3f••••••9c2D). */
    public static function mask(string $address): string
    {
        return substr($address, 0, 6) . '••••••' . substr($address, -4);
    }

    /** OG 카드용 단축: 0x + 첫 2 + … + 끝 4 (예: 0x12…ab34). */
    public static function short(string $address): string
    {
        return substr($address, 0, 4) . '…' . substr($address, -4);
    }
}
