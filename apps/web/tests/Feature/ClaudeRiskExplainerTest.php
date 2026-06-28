<?php

namespace Tests\Feature;

use App\Scan\ClaudeRiskExplainer;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeRiskExplainerTest extends TestCase
{
    /** @return array<string,mixed> */
    private function facts(): array
    {
        return [
            'token' => 'USDC',
            'spenderReputation' => 'MALICIOUS',
            'spenderType' => 'CONTRACT',
            'unlimited' => true,
            'signals' => ['Unlimited approval', 'Malicious spender'],
            'severity' => 'URGENT',
        ];
    }

    /** @return array<string,mixed> */
    private function body(string $text, string $stop = 'end_turn'): array
    {
        return [
            'content' => [['type' => 'text', 'text' => $text]],
            'stop_reason' => $stop,
            'usage' => ['input_tokens' => 12, 'output_tokens' => 24],
        ];
    }

    private function explainer(): ClaudeRiskExplainer
    {
        return new ClaudeRiskExplainer('sk-ant-test', 'claude-haiku-4-5');
    }

    public function testReturnsModelTextOnValidGroundedResponse(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('A spender flagged as malicious holds an unlimited approval on your USDC and can move it at any time.')
        )]);

        $out = $this->explainer()->explain($this->facts());

        $this->assertNotNull($out);
        $this->assertStringContainsString('USDC', $out);
    }

    public function testReturnsNullAndSendsNothingWhenNoApiKey(): void
    {
        Http::fake();

        $this->assertNull((new ClaudeRiskExplainer('', 'claude-haiku-4-5'))->explain($this->facts()));
        Http::assertNothingSent();
    }

    public function testReturnsNullOnRefusal(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response($this->body('', 'refusal'))]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testReturnsNullOnHttpError(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response('upstream error', 500)]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testRejectsFinancialAdvice(): void
    {
        // grounded (mentions USDC) but contains forbidden trading verb → must reject
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('This is risky — you should sell your USDC and swap into ETH now.')
        )]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testRejectsFabricatedAddress(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('The spender 0xdeadbeefcafe controls your USDC approval.')
        )]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testRejectsUngroundedWhenTokenMissing(): void
    {
        // never names the token → cannot be trusted as grounded → reject
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('This approval is dangerous and should be removed immediately.')
        )]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testRejectsPhishingUrl(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('Your USDC has an unlimited approval; secure it at https://wallet-rescue.io now.')
        )]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testRejectsBareDomain(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('Your USDC approval is risky; full details at wallet-rescue.io for you.')
        )]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testRejectsFundMovementDirective(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('Your USDC is at risk; send your tokens to a fresh wallet right away.')
        )]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testSkipsApiCallForShortSymbol(): void
    {
        Http::fake();

        $out = (new ClaudeRiskExplainer('sk-ant-test', 'claude-haiku-4-5'))
            ->explain(['token' => 'AB'] + $this->facts());

        $this->assertNull($out);
        Http::assertNothingSent();
    }

    public function testRejectsWhenTokenNotAWholeWord(): void
    {
        // 'USDC' appears only inside 'USDCoin' → not a whole-word mention → ungrounded → reject
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('The USDCoin contract holds an unlimited allowance and may be unsafe.')
        )]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testExtractsTextWhenFirstBlockIsThinking(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [
                ['type' => 'thinking', 'thinking' => 'reasoning...'],
                ['type' => 'text', 'text' => 'A malicious spender holds an unlimited approval on your USDC.'],
            ],
            'stop_reason' => 'end_turn',
        ])]);

        $out = $this->explainer()->explain($this->facts());

        $this->assertNotNull($out);
        $this->assertStringContainsString('USDC', $out);
    }

    public function testReturnsNullWhenNoContent(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(['stop_reason' => 'end_turn'])]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testReturnsNullOnConnectionException(): void
    {
        Http::fake(['api.anthropic.com/*' => static function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        }]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }

    public function testReturnsNullWhenTooLong(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response(
            $this->body('Your USDC approval is risky. ' . str_repeat('very ', 120))
        )]);

        $this->assertNull($this->explainer()->explain($this->facts()));
    }
}
