<?php

namespace Tests\Feature;

use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusGateway;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ScanCostIsolationTest extends TestCase
{
    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function fakeGateway(): FakeGoPlusGateway
    {
        return new FakeGoPlusGateway([
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b8', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => ['is_contract' => 1, 'is_open_source' => 1, 'malicious_behavior' => ['x'], 'doubt_list' => 0, 'trust_list' => 0]],
            ]],
        ]);
    }

    public function testCacheServesSecondRequestWithoutHittingGateway(): void
    {
        $gateway = $this->fakeGateway();
        $this->instance(GoPlusGateway::class, $gateway);

        $this->get("/scan/{$this->address}")->assertOk();
        $this->get("/scan/{$this->address}")->assertOk();

        $this->assertSame(1, $gateway->calls); // 24h 캐시: 두 번째는 게이트웨이 미호출
    }

    public function testIpDailyLimitBlocksFurtherFreshScans(): void
    {
        config(['scan.ip_daily_limit' => 1]);
        $this->instance(GoPlusGateway::class, $this->fakeGateway());

        $this->get("/scan/{$this->address}")->assertOk();                       // 1회차 허용
        $other = '0x1111111111111111111111111111111111111111';
        $this->get("/scan/{$other}")->assertSee('Daily scan limit reached');    // IP 한도 초과
    }

    public function testGatewayFailureShowsStaleNotHealthy(): void
    {
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([], true));

        $response = $this->get("/scan/{$this->address}");

        $response->assertOk();
        $response->assertSee('STALE');
        $response->assertSee("couldn't complete this scan", false);
        $response->assertDontSee('No risky approvals found'); // 정직성: 실패를 healthy 로 위장 금지
    }

    public function testStoreVerifiesTurnstileAndRedirects(): void
    {
        // 시크릿 미설정 → Turnstile 우회. 유효 주소면 결과 페이지로 리다이렉트.
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->post('/scan', ['address' => $this->address])
            ->assertRedirect('/scan/' . $this->address);
    }

    public function testStoreRejectsMalformedAddress(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->from('/')
            ->post('/scan', ['address' => 'nope'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('address');
    }
}
