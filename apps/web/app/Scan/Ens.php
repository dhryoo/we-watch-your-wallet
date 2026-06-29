<?php

namespace App\Scan;

use kornrunner\Keccak;

/**
 * ENS 이름 헬퍼(순수). EIP-137 namehash + 입력이 ENS 이름꼴인지 판별.
 * 이더리움은 NIST SHA3가 아닌 keccak256을 쓰므로 kornrunner/keccak 사용.
 */
class Ens
{
    /** name.tld 형태(.eth 등) — 0x 주소가 아닌 점 포함 라벨. */
    private const NAME_RE = '/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*\.eth$/';

    /** EIP-137 namehash. 반환: 0x + 64 hex. */
    public static function namehash(string $name): string
    {
        $node = str_repeat("\0", 32);

        if ($name !== '')
        {
            foreach (array_reverse(explode('.', strtolower($name))) as $label)
            {
                $node = hex2bin(Keccak::hash($node . hex2bin(Keccak::hash($label, 256)), 256));
            }
        }

        return '0x' . bin2hex($node);
    }

    /** 입력이 ENS .eth 이름꼴인가(해석 시도 대상). */
    public static function looksLikeName(string $input): bool
    {
        return preg_match(self::NAME_RE, strtolower(trim($input))) === 1;
    }
}
