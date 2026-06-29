<?php

namespace App\Scan;

/**
 * 지원 체인 레지스트리. GoPlus token_approval_security가 동작하는 EVM 체인만(스모크 검증).
 * Optimism은 GoPlus가 "Main chain does not exist"(code 2018)로 미지원 → 제외.
 * nft=false 체인(Base·BSC)은 GoPlus NFT approval 엔드포인트가 없어 NFT 검사를 건너뛴다(거짓 STALE 방지).
 */
class Chain
{
    public const DEFAULT = 'ethereum';

    /** slug => [id, label, nft] */
    private const CHAINS = [
        'ethereum' => ['id' => 1,     'label' => 'Ethereum',  'nft' => true],
        'base'     => ['id' => 8453,  'label' => 'Base',      'nft' => false],
        'arbitrum' => ['id' => 42161, 'label' => 'Arbitrum',  'nft' => true],
        'polygon'  => ['id' => 137,   'label' => 'Polygon',   'nft' => true],
        'bsc'      => ['id' => 56,    'label' => 'BNB Chain', 'nft' => false],
    ];

    /** @return array{slug:string,id:int,label:string,nft:bool}|null */
    public static function fromSlug(string $slug): ?array
    {
        $slug = strtolower($slug);

        if (!isset(self::CHAINS[$slug]))
        {
            return null;
        }

        return ['slug' => $slug] + self::CHAINS[$slug];
    }

    /** @return list<string> */
    public static function slugs(): array
    {
        return array_keys(self::CHAINS);
    }

    /** @return list<array{slug:string,id:int,label:string,nft:bool}> UI 드롭다운용 */
    public static function all(): array
    {
        return array_map(static fn (string $slug): array => self::fromSlug($slug), self::slugs());
    }

    public static function supportsNft(int $chainId): bool
    {
        foreach (self::CHAINS as $chain)
        {
            if ($chain['id'] === $chainId)
            {
                return $chain['nft'];
            }
        }

        return false; // 알 수 없는 체인은 NFT 미시도(거짓 STALE 방지)
    }

    public static function labelFor(int $chainId): string
    {
        foreach (self::CHAINS as $chain)
        {
            if ($chain['id'] === $chainId)
            {
                return $chain['label'];
            }
        }

        return 'Chain ' . $chainId;
    }

    /** 결과/공유 경로. ethereum은 하위호환 `/scan/{addr}`, 그 외 `/scan/{slug}/{addr}`. */
    public static function scanPath(string $slug, string $address): string
    {
        $slug = strtolower($slug);

        return $slug === self::DEFAULT ? "/scan/{$address}" : "/scan/{$slug}/{$address}";
    }
}
