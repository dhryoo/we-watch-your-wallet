<?php

namespace Tests\Unit;

use App\Scan\Address;
use PHPUnit\Framework\TestCase;

class AddressTest extends TestCase
{
    public function testMasksMiddleWithBullets(): void
    {
        $address = '0x7a3fAbCdEf0123456789AbCdEf01234567899c2D';

        // 0x + 첫 4 hex + •••••• + 끝 4 hex (레퍼런스 시안 기준)
        $this->assertSame('0x7a3f••••••9c2D', Address::mask($address));
    }

    public function testShortensWithEllipsisForOgCard(): void
    {
        $address = '0x1234567890abcdef1234567890abcdefab34';

        $this->assertSame('0x12…ab34', Address::short($address));
    }
}
