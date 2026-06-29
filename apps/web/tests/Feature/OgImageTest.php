<?php

namespace Tests\Feature;

use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusGateway;
use App\Scan\OgImage;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OgImageTest extends TestCase
{
    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @param array<string,mixed> $over */
    private function riskResult(array $over = []): array
    {
        return array_merge([
            'score' => 82, 'severity' => 'URGENT', 'approvalsReviewed' => 12,
            'stale' => false, 'failed' => false, 'risks' => [['a'], ['b']],
        ], $over);
    }

    private function bindRisky(): void
    {
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => ['is_contract' => 1, 'is_open_source' => 1, 'malicious_behavior' => ['transfer_without_approval'], 'doubt_list' => 0, 'trust_list' => 0]],
            ]],
        ]));
    }

    public function testRendersPngOfExactOgSize(): void
    {
        $png = app(OgImage::class)->render($this->riskResult(), '0x7a…9c2D');

        $info = getimagesizefromstring($png);
        $this->assertNotFalse($info);
        $this->assertSame(1200, $info[0]);
        $this->assertSame(630, $info[1]);
        $this->assertSame('image/png', $info['mime']);
    }

    public function testFailedScanDoesNotRenderIdenticallyToCleanScan(): void
    {
        // 정직성: 실패한 스캔이 깨끗한(0건) 스캔과 똑같은 카드로 렌더되면 안 된다.
        $clean = app(OgImage::class)->render($this->riskResult(['failed' => false, 'severity' => 'INFO', 'score' => 0, 'risks' => []]), '0x7a…9c2D');
        $failed = app(OgImage::class)->render($this->riskResult(['failed' => true, 'severity' => 'WATCH', 'score' => 0, 'risks' => []]), '0x7a…9c2D');

        $this->assertNotSame($clean, $failed);
        $info = getimagesizefromstring($failed);
        $this->assertSame(1200, $info[0]);
        $this->assertSame(630, $info[1]);
    }

    public function testOgPngEndpointReturnsPngImage(): void
    {
        $this->bindRisky();

        $response = $this->get("/scan/{$this->address}/og-image");

        $response->assertOk();
        $this->assertStringContainsString('image/png', $response->headers->get('content-type'));

        $info = getimagesizefromstring($response->getContent());
        $this->assertSame(1200, $info[0]);
        $this->assertSame(630, $info[1]);
    }

    public function testOgPngRejectsMalformedAddress(): void
    {
        $this->get('/scan/not-an-address/og-image')->assertNotFound();
    }

    public function testScanResultOgImageMetaPointsToPng(): void
    {
        $this->bindRisky();

        $this->get("/scan/{$this->address}")
            ->assertOk()
            ->assertSee('/scan/' . $this->address . '/og-image', false);
    }
}
