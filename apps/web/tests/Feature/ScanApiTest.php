<?php

namespace Tests\Feature;

use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusGateway;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ScanApiTest extends TestCase
{
    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function riskyGateway(): FakeGoPlusGateway
    {
        return new FakeGoPlusGateway([
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b8', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => ['is_contract' => 1, 'is_open_source' => 1, 'malicious_behavior' => ['x'], 'doubt_list' => 0, 'trust_list' => 0]],
            ]],
        ]);
    }

    public function testReturnsJsonForRiskyWallet(): void
    {
        $this->instance(GoPlusGateway::class, $this->riskyGateway());

        $response = $this->getJson('/api/scan/' . $this->address);

        $response->assertOk();
        $response->assertJsonPath('severity', 'URGENT');
        $response->assertJsonPath('risks.0.token', 'USDC');
        $response->assertJsonPath('risks.0.spender', 'MALICIOUS');
        $response->assertJsonStructure(['address', 'chainId', 'score', 'severity', 'risks' => [['token', 'severity', 'unlimited', 'revokeUrl']], 'disclaimer']);
        $response->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function testInvalidAddressReturns422Json(): void
    {
        $response = $this->getJson('/api/scan/not-an-address');

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'invalid_address');
    }

    public function testRateLimitedReturns429Json(): void
    {
        config(['scan.ip_daily_limit' => 1]);
        $this->instance(GoPlusGateway::class, $this->riskyGateway());

        $this->getJson('/api/scan/' . $this->address)->assertOk();
        $other = '0x1111111111111111111111111111111111111111';
        $this->getJson('/api/scan/' . $other)->assertStatus(429)->assertJsonPath('error', 'rate_limited');
    }

    public function testGatewayFailureReturns503NotCleanLooking200(): void
    {
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([], true));

        $response = $this->getJson('/api/scan/' . $this->address);

        // honesty: upstream failure must not look like a clean 200 (score:0/risks:[])
        $response->assertStatus(503);
        $response->assertJsonPath('error', 'scan_unavailable');
        $response->assertJsonPath('failed', true);
    }

    public function testApiIsSessionless(): void
    {
        $this->instance(GoPlusGateway::class, $this->riskyGateway());

        $response = $this->getJson('/api/scan/' . $this->address);

        $response->assertOk();
        $this->assertNull($response->headers->get('set-cookie')); // no session/cookie on the public API
    }
}
