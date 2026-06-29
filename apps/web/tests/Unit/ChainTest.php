<?php

namespace Tests\Unit;

use App\Scan\Chain;
use PHPUnit\Framework\TestCase;

class ChainTest extends TestCase
{
    public function testFromSlugReturnsChainData(): void
    {
        $base = Chain::fromSlug('base');

        $this->assertSame('base', $base['slug']);
        $this->assertSame(8453, $base['id']);
        $this->assertSame('Base', $base['label']);
        $this->assertFalse($base['nft']);
    }

    public function testFromSlugIsCaseInsensitive(): void
    {
        $this->assertSame(1, Chain::fromSlug('ETHEREUM')['id']);
    }

    public function testUnsupportedChainsAreNull(): void
    {
        // Optimism은 GoPlus token_approval 미지원이라 제외.
        $this->assertNull(Chain::fromSlug('optimism'));
        $this->assertNull(Chain::fromSlug('dogechain'));
        $this->assertNull(Chain::fromSlug(''));
    }

    public function testSlugsAreTheCuratedFive(): void
    {
        $this->assertSame(['ethereum', 'base', 'arbitrum', 'polygon', 'bsc'], Chain::slugs());
    }

    public function testSupportsNftReflectsGoPlusCoverage(): void
    {
        $this->assertTrue(Chain::supportsNft(1));      // ethereum
        $this->assertTrue(Chain::supportsNft(42161));  // arbitrum
        $this->assertTrue(Chain::supportsNft(137));    // polygon
        $this->assertFalse(Chain::supportsNft(8453));  // base — token only
        $this->assertFalse(Chain::supportsNft(56));    // bsc — token only
        $this->assertFalse(Chain::supportsNft(999999)); // unknown
    }

    public function testScanPathKeepsEthereumBackCompatAndPrefixesOthers(): void
    {
        $this->assertSame('/scan/0xAbc', Chain::scanPath('ethereum', '0xAbc'));
        $this->assertSame('/scan/base/0xAbc', Chain::scanPath('base', '0xAbc'));
        $this->assertSame('/scan/arbitrum/0xAbc', Chain::scanPath('arbitrum', '0xAbc'));
    }

    public function testLabelForChainId(): void
    {
        $this->assertSame('Ethereum', Chain::labelFor(1));
        $this->assertSame('BNB Chain', Chain::labelFor(56));
    }
}
