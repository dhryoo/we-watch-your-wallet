<?php

namespace Tests\Feature;

use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusGateway;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ScanPageTest extends TestCase
{
    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function spender(int $contract, int $openSource, array $malicious, int $trust): array
    {
        return ['is_contract' => $contract, 'is_open_source' => $openSource, 'malicious_behavior' => $malicious, 'doubt_list' => 0, 'trust_list' => $trust];
    }

    private function bindRisky(): void
    {
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => $this->spender(1, 1, ['transfer_without_approval'], 0)],
            ]],
            ['token_symbol' => 'WETH', 'token_address' => '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2', 'balance' => '5', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => $this->spender(1, 0, [], 0)],
            ]],
        ]));
    }

    public function testScanResultRendersRealRiskyApprovals(): void
    {
        $this->bindRisky();

        $response = $this->get("/scan/{$this->address}");

        $response->assertOk();
        $response->assertSee('risky token approvals');
        $response->assertSee('URGENT');
        $response->assertSee('USDC');
        $response->assertSee('Open revoke page');
        $response->assertSee("revoke.cash/address/{$this->address}?chainId=1", false);
        $response->assertSee('0x7a3f••••••9c2D');
        $response->assertSee('not financial, investment, or legal advice');
    }

    public function testHealthyWalletRendersEmptyState(): void
    {
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b8', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => '1000', 'approved_time' => null, 'address_info' => $this->spender(1, 1, [], 1)],
            ]],
        ]));

        $response = $this->get("/scan/{$this->address}");

        $response->assertOk();
        $response->assertSee('No risky approvals found');
        $response->assertDontSee('risky token approvals');
    }

    public function testOgCardMasksAddressAndHidesFullAddress(): void
    {
        $this->bindRisky();

        $response = $this->get("/scan/{$this->address}/og");

        $response->assertOk();
        $response->assertSee('0x7a…9c2D');
        $response->assertSee('Scan your wallet free');
        $response->assertDontSee($this->address, false);
    }

    public function testDemoModeStillRendersDesignMock(): void
    {
        // ?demo=1 은 GoPlus 미호출(디자인 미리보기). STALE 배너 포함.
        $response = $this->get("/scan/{$this->address}?demo=1");

        $response->assertOk();
        $response->assertSee('risky token approvals');
        $response->assertSee('STALE');
    }

    public function testRejectsMalformedAddress(): void
    {
        $this->get('/scan/not-an-address')->assertNotFound();
    }

    public function testOgCardShowsUnavailableInsteadOfFakeCleanWhenScanFails(): void
    {
        // 정직성: 스캔 실패를 "0 found · score 0"로 공유하지 않는다(가짜 clean 금지).
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([], shouldFail: true));

        $response = $this->get("/scan/{$this->address}/og");

        $response->assertOk();
        $response->assertSee('Scan unavailable');
        $response->assertSee('not');
        $response->assertDontSee('Risky approvals');
        $response->assertDontSee('Risk score');
    }

    public function testEmailPreviewIsNotAvailableInProduction(): void
    {
        // 이메일 템플릿 미리보기는 로컬 전용(메일러 미구현 → 실주소에 가짜 데이터 노출 방지).
        $this->get("/scan/{$this->address}/email")->assertNotFound();
    }
}
