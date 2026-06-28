<?php

namespace App\Scan;

/**
 * QR/붙여넣기 텍스트에서 이더리움 주소(0x + 40 hex)를 추출.
 * 평문 주소, EIP-681(ethereum:0x…@1 / ?value=) 허용. 64-hex 해시·WalletConnect(wc:)는 거부.
 */
class WalletAddress
{
    private const PATTERN = '/0x[0-9a-fA-F]{40}(?![0-9a-fA-F])/';

    public static function extract(string $text): ?string
    {
        if (preg_match(self::PATTERN, $text, $matches) === 1)
        {
            return $matches[0];
        }

        return null;
    }
}
