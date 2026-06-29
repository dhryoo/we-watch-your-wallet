<?php

namespace Tests\Unit;

use App\Scan\Ens;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnsTest extends TestCase
{
    public function testNamehashOfEmptyNameIsAllZeros(): void
    {
        $this->assertSame(
            '0x0000000000000000000000000000000000000000000000000000000000000000',
            Ens::namehash('')
        );
    }

    public function testNamehashOfEthMatchesEip137Vector(): void
    {
        // EIP-137 published vector.
        $this->assertSame(
            '0x93cdeb708b7545dc668eb9280176169d1c33cfd8ed6f04690a0bcc88a93fc4ae',
            Ens::namehash('eth')
        );
    }

    public function testNamehashIsCaseInsensitive(): void
    {
        $this->assertSame(Ens::namehash('vitalik.eth'), Ens::namehash('VITALIK.ETH'));
    }

    #[DataProvider('nameLikeInputs')]
    public function testLooksLikeName(string $input, bool $expected): void
    {
        $this->assertSame($expected, Ens::looksLikeName($input));
    }

    /** @return array<string,array{0:string,1:bool}> */
    public static function nameLikeInputs(): array
    {
        return [
            'plain eth name' => ['vitalik.eth', true],
            'subdomain' => ['app.uniswap.eth', true],
            'uppercase' => ['VITALIK.ETH', true],
            'surrounding spaces' => ['  vitalik.eth  ', true],
            '0x address' => ['0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D', false],
            'no tld' => ['vitalik', false],
            'non-eth tld' => ['vitalik.xyz', false],
            'empty' => ['', false],
            'just dot eth' => ['.eth', false],
        ];
    }
}
