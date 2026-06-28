<?php

namespace Tests\Unit;

use App\Scan\WalletAddress;
use PHPUnit\Framework\TestCase;

class WalletAddressTest extends TestCase
{
    private string $address = '0x4862733B5FdDFd35f35ea8CCf08F5045e57388B3';

    public function testExtractsPlainAddress(): void
    {
        $this->assertSame($this->address, WalletAddress::extract($this->address));
    }

    public function testExtractsFromEip681Uri(): void
    {
        // 지갑 받기 QR 은 흔히 EIP-681 URI.
        $this->assertSame($this->address, WalletAddress::extract("ethereum:{$this->address}@1"));
        $this->assertSame($this->address, WalletAddress::extract("ethereum:{$this->address}?value=1.5"));
    }

    public function testPreservesChecksumCaseWithinText(): void
    {
        $this->assertSame($this->address, WalletAddress::extract("scan result: {$this->address} ok"));
    }

    public function testReturnsNullWhenNoAddress(): void
    {
        $this->assertNull(WalletAddress::extract('nope'));
        $this->assertNull(WalletAddress::extract('0x123'));                          // too short
        $this->assertNull(WalletAddress::extract('wc:abc123@2?relay-protocol=irn')); // WalletConnect, not an address
    }

    public function testDoesNotMatchLongerHexAsAddress(): void
    {
        $txHash = '0x' . str_repeat('a', 64); // 64 hex = tx hash, not a 40-hex address
        $this->assertNull(WalletAddress::extract($txHash));
    }
}
