<?php

namespace Tests\Unit;

use App\Scan\ScanResult;
use PHPUnit\Framework\TestCase;

class ScanResultTest extends TestCase
{
    public function testSortsRisksBySeverityDescending(): void
    {
        $risks = [
            ['token' => 'A', 'severity' => 'WATCH'],
            ['token' => 'B', 'severity' => 'URGENT'],
            ['token' => 'C', 'severity' => 'INFO'],
            ['token' => 'D', 'severity' => 'WARN'],
        ];

        $sorted = array_map(
            static fn (array $risk): string => $risk['severity'],
            ScanResult::sortBySeverityDesc($risks)
        );

        $this->assertSame(['URGENT', 'WARN', 'WATCH', 'INFO'], $sorted);
    }
}
