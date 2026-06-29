<?php

namespace Tests\Unit;

use App\Scan\Address;
use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusScanner;
use App\Scan\RiskExplainer;
use PHPUnit\Framework\TestCase;

class GoPlusScannerTest extends TestCase
{
    /** @return array<int,array<string,mixed>> */
    private function spender(int $contract, int $openSource, array $malicious, int $doubt, int $trust): array
    {
        return [
            'is_contract' => $contract,
            'is_open_source' => $openSource,
            'malicious_behavior' => $malicious,
            'doubt_list' => $doubt,
            'trust_list' => $trust,
        ];
    }

    private function riskyFixture(): array
    {
        return [
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => $this->spender(1, 1, ['transfer_without_approval'], 0, 0)],
            ]],
            ['token_symbol' => 'WETH', 'token_address' => '0xC02aaA39b223FE8D0A0e5C4F27eAD9083C756Cc2', 'balance' => '5', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => $this->spender(1, 0, [], 0, 0)],
            ]],
            ['token_symbol' => 'DAI', 'token_address' => '0x6B175474E89094C44Da98b954EedeAC495271d0F', 'balance' => '2000', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => $this->spender(1, 1, [], 0, 1)],
            ]],
        ];
    }

    public function testScoresAndSortsRiskyApprovals(): void
    {
        $scanner = new GoPlusScanner(new FakeGoPlusGateway($this->riskyFixture()));
        $result = $scanner->scan('0xWALLETabc', 1);

        $this->assertFalse($result['failed']);
        $this->assertSame(3, $result['approvalsReviewed']);
        $this->assertCount(3, $result['risks']);
        $this->assertSame(['URGENT', 'WARN', 'WATCH'], array_map(static fn ($r) => $r['severity'], $result['risks']));
        $this->assertSame('URGENT', $result['severity']);
        $this->assertSame(80, $result['score']); // malicious 60 + unlimited 20

        $top = $result['risks'][0];
        $this->assertSame('USDC', $top['token']);
        $this->assertSame('MALICIOUS', $top['spenderTag']);
        $this->assertTrue($top['unlimited']);
        $this->assertStringContainsString('revoke.cash/address/0xWALLETabc?chainId=1', $top['revokeUrl']);
        $this->assertNotEmpty($top['explain']);
        $this->assertNotEmpty($top['checklist']);
    }

    public function testHealthyWalletHasNoRisks(): void
    {
        $fixture = [
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b8', 'balance' => '1000', 'approved_list' => [
                ['approved_amount' => '1000', 'approved_time' => null, 'address_info' => $this->spender(1, 1, [], 0, 1)],
            ]],
        ];

        $result = (new GoPlusScanner(new FakeGoPlusGateway($fixture)))->scan('0xWALLET', 1);

        $this->assertSame([], $result['risks']);
        $this->assertSame('INFO', $result['severity']);
        $this->assertSame(1, $result['approvalsReviewed']);
        $this->assertFalse($result['failed']);
    }

    public function testSkipsNftScanOnTokenOnlyChainsSoNoFalseStale(): void
    {
        // base(8453)는 GoPlus NFT approval 미지원 → NFT 시도 자체를 안 해 거짓 STALE이 생기면 안 된다.
        $gateway = new FakeGoPlusGateway($this->riskyFixture(), nftShouldFail: true);
        $scanner = new GoPlusScanner($gateway);

        $base = $scanner->scan('0xWALLET', 8453);
        $this->assertFalse($base['stale']);

        // ethereum(1)은 NFT 지원 → NFT 실패를 정직하게 STALE로 표기.
        $eth = $scanner->scan('0xWALLET', 1);
        $this->assertTrue($eth['stale']);
    }

    public function testGatewayFailureProducesStaleNotHealthy(): void
    {
        $result = (new GoPlusScanner(new FakeGoPlusGateway([], true)))->scan('0xWALLET', 1);

        $this->assertTrue($result['failed']);
        $this->assertTrue($result['stale']);
        $this->assertNotSame('INFO', $result['severity']); // 정직성: 실패를 healthy 로 위장 금지
        $this->assertSame([], $result['risks']);
    }

    private function explainer(callable $fn): RiskExplainer
    {
        return new class ($fn) implements RiskExplainer {
            /** @param callable $fn */
            public function __construct(private $fn)
            {
            }

            public function explain(array $facts): ?string
            {
                return ($this->fn)($facts);
            }
        };
    }

    public function testUsesLlmExplanationWhenAvailable(): void
    {
        $explainer = $this->explainer(static fn (array $facts): string => 'LLM: your ' . $facts['token'] . ' approval is risky.');
        $scanner = new GoPlusScanner(new FakeGoPlusGateway($this->riskyFixture()), $explainer);

        $top = $scanner->scan('0xWALLET', 1)['risks'][0];

        $this->assertSame('LLM: your USDC approval is risky.', $top['explain']);
    }

    public function testFallsBackToTemplateWhenLlmReturnsNull(): void
    {
        $explainer = $this->explainer(static fn (array $facts): ?string => null);
        $scanner = new GoPlusScanner(new FakeGoPlusGateway($this->riskyFixture()), $explainer);

        $top = $scanner->scan('0xWALLET', 1)['risks'][0];

        $this->assertNotEmpty($top['explain']);
        $this->assertStringContainsString('malicious', strtolower($top['explain'])); // 템플릿(MALICIOUS) 문구
    }

    public function testFallsBackToTemplateWhenLlmThrows(): void
    {
        $explainer = $this->explainer(static function (array $facts): string {
            throw new \RuntimeException('boom');
        });
        $scanner = new GoPlusScanner(new FakeGoPlusGateway($this->riskyFixture()), $explainer);

        $top = $scanner->scan('0xWALLET', 1)['risks'][0]; // 예외가 스캔을 죽이면 안 됨

        $this->assertNotEmpty($top['explain']);
        $this->assertStringContainsString('malicious', strtolower($top['explain']));
    }

    public function testCapsLlmToTopRiskByDefault(): void
    {
        $explainer = $this->explainer(static fn (array $facts): string => 'LLM:' . $facts['token']);
        $risks = (new GoPlusScanner(new FakeGoPlusGateway($this->riskyFixture()), $explainer))->scan('0xW', 1)['risks'];

        $this->assertStringStartsWith('LLM:', $risks[0]['explain']);          // 최상위만 LLM
        $this->assertStringNotContainsString('LLM:', $risks[1]['explain']);   // 나머지는 템플릿
        $this->assertStringNotContainsString('LLM:', $risks[2]['explain']);
    }

    public function testRespectsConfiguredLlmCap(): void
    {
        $explainer = $this->explainer(static fn (array $facts): string => 'LLM:' . $facts['token']);
        $risks = (new GoPlusScanner(new FakeGoPlusGateway($this->riskyFixture()), $explainer, 2))->scan('0xW', 1)['risks'];

        $this->assertStringStartsWith('LLM:', $risks[0]['explain']);
        $this->assertStringStartsWith('LLM:', $risks[1]['explain']);
        $this->assertStringNotContainsString('LLM:', $risks[2]['explain']);
    }

    private function nftFixture(): array
    {
        return [
            ['nft_symbol' => 'BAYC', 'nft_address' => '0xBC4CA0EdA7647A8aB7C2061c2E118A18a936f13D', 'approved_list' => [
                ['approved_contract' => '0x1E0049783F008A0085193E00003D00cd54003c71', 'approved_time' => null, 'address_info' => $this->spender(1, 1, ['transfer_without_approval'], 0, 0)],
            ]],
        ];
    }

    public function testIncludesNftSetApprovalForAllRisks(): void
    {
        $scanner = new GoPlusScanner(new FakeGoPlusGateway([], false, $this->nftFixture()));
        $result = $scanner->scan('0xWALLET', 1);

        $tokens = array_map(static fn ($r) => $r['token'], $result['risks']);
        $this->assertContains('BAYC', $tokens);

        $bayc = $result['risks'][array_search('BAYC', $tokens, true)];
        $this->assertSame('URGENT', $bayc['severity']);          // malicious operator → URGENT
        $this->assertContains('Approval for all items', $bayc['signals']); // setApprovalForAll signal
        $this->assertFalse($result['failed']);
    }

    public function testNftFetchFailureMarksStaleButKeepsTokenRisks(): void
    {
        // token approvals OK, NFT endpoint fails → partial data: token risks kept, marked stale (not failed).
        $scanner = new GoPlusScanner(new FakeGoPlusGateway($this->riskyFixture(), false, [], true));
        $result = $scanner->scan('0xWALLET', 1);

        $this->assertFalse($result['failed']);
        $this->assertTrue($result['stale']);
        $this->assertNotSame([], $result['risks']); // token risks still present
    }

    public function testSurfacesSpenderContractAddress(): void
    {
        $fixture = [
            ['token_symbol' => 'USDC', 'token_address' => '0xA0b8', 'balance' => '1', 'approved_list' => [
                ['approved_contract' => '0x1111111111111111111111111111111111111111', 'approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => $this->spender(1, 1, ['x'], 0, 0)],
            ]],
        ];

        $top = (new GoPlusScanner(new FakeGoPlusGateway($fixture)))->scan('0xW', 1)['risks'][0];

        $this->assertNotSame('', $top['spenderAddress']);
        $this->assertStringStartsWith('0x', $top['spenderAddress']); // 어느 컨트랙트를 revoke할지
        $this->assertSame(Address::short('0x1111111111111111111111111111111111111111'), $top['spenderAddress']);
    }

    public function testPartialGoPlusResponseMarksStale(): void
    {
        // GoPlus code 2(부분) 또는 NFT 한쪽 실패 → wasPartial → 결과 STALE(거짓 clean 방지).
        $scanner = new GoPlusScanner(new FakeGoPlusGateway($this->riskyFixture(), false, [], false, true));
        $result = $scanner->scan('0xW', 1);

        $this->assertTrue($result['stale']);
        $this->assertFalse($result['failed']);
        $this->assertNotSame([], $result['risks']);
    }

    public function testSanitizesAttackerControlledTokenSymbol(): void
    {
        $fixture = [
            ['token_symbol' => "EVIL https://wallet-rescue.io\nclaim your funds now immediately", 'token_address' => '0xA0b8', 'balance' => '1', 'approved_list' => [
                ['approved_amount' => 'unlimited', 'approved_time' => null, 'address_info' => $this->spender(1, 1, ['x'], 0, 0)],
            ]],
        ];

        $token = (new GoPlusScanner(new FakeGoPlusGateway($fixture)))->scan('0xW', 1)['risks'][0]['token'];

        $this->assertLessThanOrEqual(16, mb_strlen($token));
        $this->assertDoesNotMatchRegularExpression('~[^\p{L}\p{N} _-]~u', $token); // 안전 charset만
        $this->assertStringNotContainsString('://', $token);
        $this->assertStringNotContainsString("\n", $token);
    }
}
