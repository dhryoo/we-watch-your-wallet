<?php

namespace Tests\Feature;

use App\Scan\EnsResolver;
use App\Scan\FakeEnsResolver;
use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusGateway;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MultichainTest extends TestCase
{
    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function bindRisky(): void
    {
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => ['is_contract' => 1, 'is_open_source' => 1, 'malicious_behavior' => ['transfer_without_approval'], 'doubt_list' => 0, 'trust_list' => 0]],
            ]],
        ]));
    }

    public function testChainPathScansWithThatChainId(): void
    {
        $this->bindRisky();

        $this->get("/scan/base/{$this->address}")
            ->assertOk()
            ->assertSee('Base')
            ->assertSee('chainId=8453', false); // revoke link carries the chain
    }

    public function testEthereumBackCompatPathStillWorks(): void
    {
        $this->bindRisky();

        $this->get("/scan/{$this->address}")
            ->assertOk()
            ->assertSee('Ethereum')
            ->assertSee('chainId=1', false);
    }

    public function testUnsupportedChainSlugIs404(): void
    {
        $this->get("/scan/optimism/{$this->address}")->assertNotFound();
        $this->get("/scan/dogechain/{$this->address}")->assertNotFound();
    }

    public function testOgImageIsChainAware(): void
    {
        $this->bindRisky();

        $response = $this->get("/scan/arbitrum/{$this->address}/og-image");
        $response->assertOk();
        $this->assertStringContainsString('image/png', $response->headers->get('content-type'));

        // result page points og:image at the chain-scoped PNG
        $this->get("/scan/arbitrum/{$this->address}")
            ->assertSee('/scan/arbitrum/' . $this->address . '/og-image', false);
    }

    public function testPostRedirectsToSelectedChainPath(): void
    {
        $this->bindRisky();

        $this->post('/scan', ['address' => $this->address, 'chain' => 'base'])
            ->assertRedirect('/scan/base/' . $this->address);

        $this->post('/scan', ['address' => $this->address, 'chain' => 'ethereum'])
            ->assertRedirect('/scan/' . $this->address);
    }

    public function testPostWithUnknownChainFallsBackToEthereum(): void
    {
        $this->bindRisky();

        $this->post('/scan', ['address' => $this->address, 'chain' => 'dogechain'])
            ->assertRedirect('/scan/' . $this->address);
    }

    public function testEnsResolvesThenRedirectsToSelectedChain(): void
    {
        $this->bindRisky();
        $this->instance(EnsResolver::class, new FakeEnsResolver(['vitalik.eth' => $this->address]));

        // ENS는 항상 L1에서 해석되지만, 스캔은 선택한 체인에서.
        $this->post('/scan', ['address' => 'vitalik.eth', 'chain' => 'arbitrum'])
            ->assertRedirect('/scan/arbitrum/' . $this->address);
    }

    public function testApiIsChainAware(): void
    {
        $this->bindRisky();

        $this->getJson('/api/scan/base/' . $this->address)
            ->assertOk()
            ->assertJsonPath('chainId', 8453);

        // 하위호환: 체인 없는 경로는 ethereum.
        $this->getJson('/api/scan/' . $this->address)
            ->assertOk()
            ->assertJsonPath('chainId', 1);
    }

    public function testApiUnsupportedChainReturns422(): void
    {
        $this->getJson('/api/scan/optimism/' . $this->address)
            ->assertStatus(422)
            ->assertJsonPath('error', 'unsupported_chain');
    }

    public function testHomeOffersChainSelector(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('name="chain"', false)
            ->assertSee('Arbitrum')
            ->assertSee('BNB Chain');
    }
}
