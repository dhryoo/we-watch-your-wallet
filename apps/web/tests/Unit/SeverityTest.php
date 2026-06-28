<?php

namespace Tests\Unit;

use App\Scan\Severity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SeverityTest extends TestCase
{
    public function testReturnsDesignTokensForUrgent(): void
    {
        $tokens = Severity::tokens('URGENT');

        $this->assertSame('▲', $tokens['mark']);
        $this->assertSame('URGENT', $tokens['label']);
        $this->assertSame('#8E2018', $tokens['fg']);
        $this->assertSame('#FBEAE8', $tokens['bg']);
        $this->assertSame('#EFB5B0', $tokens['bd']);
        $this->assertSame('#B3382E', $tokens['ic']);
    }

    public function testRanksUrgentHighestAndInfoLowest(): void
    {
        $this->assertGreaterThan(Severity::rank('WARN'), Severity::rank('URGENT'));
        $this->assertGreaterThan(Severity::rank('WATCH'), Severity::rank('WARN'));
        $this->assertGreaterThan(Severity::rank('INFO'), Severity::rank('WATCH'));
    }

    public function testThrowsOnUnknownSeverity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Severity::tokens('BUYNOW');
    }
}
