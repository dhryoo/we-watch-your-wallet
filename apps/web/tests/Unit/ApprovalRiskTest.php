<?php

namespace Tests\Unit;

use App\Scan\ApprovalRisk;
use PHPUnit\Framework\TestCase;

/**
 * 결정론적 가중합 — detect-engine(TS)/architecture §2.2 와 동일 스펙.
 */
class ApprovalRiskTest extends TestCase
{
    public function testScoresIndividualWeights(): void
    {
        $this->assertSame(60, ApprovalRisk::score(['spenderReputation' => 'MALICIOUS']));
        $this->assertSame(30, ApprovalRisk::score(['spenderType' => 'EOA']));
        $this->assertSame(25, ApprovalRisk::score(['spenderReputation' => 'UNVERIFIED']));
        $this->assertSame(20, ApprovalRisk::score(['unlimited' => true]));
    }

    public function testAccumulatesAndClampsTo100(): void
    {
        $this->assertSame(100, ApprovalRisk::score([
            'spenderReputation' => 'MALICIOUS', // 60
            'spenderType' => 'EOA',             // 30
            'unlimited' => true,                // 20
            'isSetApprovalForAll' => true,      // 20 => 130 -> clamp 100
        ]));
    }

    public function testClassifyMaliciousIsAlwaysUrgent(): void
    {
        $this->assertSame('URGENT', ApprovalRisk::classify(['spenderReputation' => 'MALICIOUS']));
    }

    public function testClassifyVerifiedUnlimitedCappedAtWatch(): void
    {
        $severity = ApprovalRisk::classify([
            'spenderReputation' => 'VERIFIED',  // cap WATCH
            'unlimited' => true,                // 20
            'highValueToken' => true,           // 10
            'balanceMultipleOver50x' => true,   // 15 => 45 (WARN) -> capped WATCH
        ]);

        $this->assertContains($severity, ['INFO', 'WATCH']);
    }

    public function testClassifyScoreThresholds(): void
    {
        $this->assertSame('INFO', ApprovalRisk::classify([]));
        $this->assertSame('WATCH', ApprovalRisk::classify(['unlimited' => true]));                              // 20
        $this->assertSame('WARN', ApprovalRisk::classify(['spenderType' => 'EOA', 'unlimited' => true]));       // 50
        $this->assertSame('URGENT', ApprovalRisk::classify([
            'spenderReputation' => 'UNVERIFIED', 'spenderType' => 'EOA', 'unlimited' => true,                   // 75
        ]));
    }

    public function testDetectsUnlimitedAllowance(): void
    {
        $this->assertTrue(ApprovalRisk::isUnlimited('unlimited'));
        $this->assertTrue(ApprovalRisk::isUnlimited('115792089237316195423570985008687907853269984665640564039457'));
        $this->assertFalse(ApprovalRisk::isUnlimited('1000'));
        $this->assertFalse(ApprovalRisk::isUnlimited('79994733592576.995933315112117969'));
    }
}
