<?php

namespace Tests\Feature;

use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusGateway;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ScanLoadingTest extends TestCase
{
    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function testHomeIncludesLoadingOverlayAndTrigger(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('id="wt-loading"', false);
        $response->assertSee('Reading on-chain approvals', false);
        $response->assertSee('js/scan-loading.js', false);
        $response->assertSee('data-scan-loading', false); // the scan form
    }

    public function testLoadingScriptIsSelfHosted(): void
    {
        $this->assertFileExists(public_path('js/scan-loading.js'));
    }

    public function testResultPageRescanTriggersLoading(): void
    {
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b8', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => ['is_contract' => 1, 'is_open_source' => 1, 'malicious_behavior' => ['x'], 'doubt_list' => 0, 'trust_list' => 0]],
            ]],
        ]));

        $response = $this->get("/scan/{$this->address}");

        $response->assertOk();
        $response->assertSee('id="wt-loading"', false);
        $response->assertSee('data-scan-loading', false); // Re-scan link
    }
}
