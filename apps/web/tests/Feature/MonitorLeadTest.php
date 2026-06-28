<?php

namespace Tests\Feature;

use App\Models\MonitorLead;
use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusGateway;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class MonitorLeadTest extends TestCase
{
    use RefreshDatabase;

    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';
    private string $address2 = '0x1111111111111111111111111111111111111111';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'you@example.com',
            'wallet_address' => $this->address,
            'source' => 'result',
            'scanned_wallet' => $this->address,
            'scan_severity' => 'WARN',
            'consent' => 1,
        ], $overrides);
    }

    public function testValidSubmissionStoresLead(): void
    {
        $this->post('/monitor', $this->payload())->assertRedirect();

        $this->assertDatabaseHas('monitor_leads', [
            'email' => 'you@example.com',
            'wallet_address' => $this->address,
            'source' => 'result',
        ]);
    }

    public function testEmailIsNormalizedToLowercaseAndTrimmed(): void
    {
        $this->post('/monitor', $this->payload(['email' => '  You@Example.COM  ']));

        $this->assertDatabaseHas('monitor_leads', ['email' => 'you@example.com']);
    }

    public function testDuplicateEmailAndWalletUpdatesNotDuplicates(): void
    {
        $this->post('/monitor', $this->payload(['scan_severity' => 'WARN']));
        $this->post('/monitor', $this->payload(['scan_severity' => 'URGENT']));

        $this->assertDatabaseCount('monitor_leads', 1);
        $this->assertDatabaseHas('monitor_leads', ['scan_severity' => 'URGENT']);
    }

    public function testSameEmailDifferentWalletCreatesSecondLead(): void
    {
        $this->post('/monitor', $this->payload(['wallet_address' => $this->address]));
        $this->post('/monitor', $this->payload(['wallet_address' => $this->address2, 'scanned_wallet' => $this->address2]));

        $this->assertDatabaseCount('monitor_leads', 2);
    }

    public function testMissingOrInvalidEmailIsRejected(): void
    {
        $this->from('/scan/' . $this->address)
            ->post('/monitor', $this->payload(['email' => 'nope']))
            ->assertRedirect('/scan/' . $this->address)
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('monitor_leads', 0);
    }

    public function testConsentRequiredToPersist(): void
    {
        $this->post('/monitor', $this->payload(['consent' => 0]))
            ->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('monitor_leads', 0);
    }

    public function testHoneypotFilledIsSilentlyDropped(): void
    {
        $this->post('/monitor', $this->payload(['website' => 'bot@spam.com']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('monitorStatus', 'ok');

        $this->assertDatabaseCount('monitor_leads', 0);
    }

    public function testInvalidWalletAddressIsRejected(): void
    {
        $this->post('/monitor', $this->payload(['wallet_address' => '0xZZZ']))
            ->assertSessionHasErrors('wallet_address');

        $this->assertDatabaseCount('monitor_leads', 0);
    }

    public function testStoresIpHashNeverRawIp(): void
    {
        $this->post('/monitor', $this->payload());

        $lead = MonitorLead::first();
        $this->assertNotNull($lead);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $lead->ip_hash);
        $this->assertStringNotContainsString('127.0.0.1', $lead->ip_hash);
        $this->assertNotContains('127.0.0.1', array_values($lead->getAttributes()));
    }

    public function testPerIpDailyLimitBlocksFurtherSubmissions(): void
    {
        config(['scan.monitor_ip_daily_limit' => 1]);

        $this->post('/monitor', $this->payload(['email' => 'first@example.com']));
        $this->post('/monitor', $this->payload(['email' => 'second@example.com']))
            ->assertSessionHasErrors('monitor');

        $this->assertDatabaseCount('monitor_leads', 1);
    }

    public function testIdempotentResubmitDoesNotConsumeRateLimit(): void
    {
        config(['scan.monitor_ip_daily_limit' => 1]);

        // Re-monitoring the SAME wallet (e.g. after a re-scan) refreshes the lead for free —
        // an idempotent repeat must not spend the daily slot or lock the user out.
        $this->post('/monitor', $this->payload())->assertSessionHasNoErrors();
        $this->post('/monitor', $this->payload(['scan_severity' => 'URGENT']))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('monitorStatus', 'ok');

        $this->assertDatabaseCount('monitor_leads', 1);
        $this->assertDatabaseHas('monitor_leads', ['scan_severity' => 'URGENT']);
    }

    private function riskyGateway(): FakeGoPlusGateway
    {
        return new FakeGoPlusGateway([
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b8', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => ['is_contract' => 1, 'is_open_source' => 1, 'malicious_behavior' => ['x'], 'doubt_list' => 0, 'trust_list' => 0]],
            ]],
        ]);
    }

    public function testSuccessShowsHonestFlashNotEmailSent(): void
    {
        $this->instance(GoPlusGateway::class, $this->riskyGateway());

        $response = $this->withSession(['monitorStatus' => 'ok'])->get('/scan/' . $this->address);

        $response->assertOk();
        $response->assertSee('watch list');
        $response->assertSee('coming soon');
        $response->assertDontSee('Email sent');
        $response->assertDontSee('inbox');
    }

    public function testResultPageRendersWiredMonitorForm(): void
    {
        $this->instance(GoPlusGateway::class, $this->riskyGateway());

        $response = $this->get('/scan/' . $this->address);

        $response->assertOk();
        $response->assertSee(url('/monitor'), false);
        $response->assertDontSee('onsubmit="return false', false);
        $response->assertDontSee('action="#"', false);
    }

    public function testEmptyScanPageRendersWiredMonitorForm(): void
    {
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([]));

        $response = $this->get('/scan/' . $this->address);

        $response->assertOk();
        $response->assertSee('No risky approvals found');
        $response->assertSee(url('/monitor'), false);
        $response->assertSee('name="email"', false); // a real wired email form, not the old dead anchor
        $response->assertDontSee('>Monitor this wallet</a>', false);
    }
}
