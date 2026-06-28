<?php

namespace Tests\Feature;

use App\Scan\Turnstile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    public function testBypassesWhenNoSecretConfigured(): void
    {
        $this->assertTrue((new Turnstile(null))->verify(null));
        $this->assertTrue((new Turnstile(''))->verify('anything'));
    }

    public function testRejectsMissingTokenWhenSecretSet(): void
    {
        $this->assertFalse((new Turnstile('secret'))->verify(null));
        $this->assertFalse((new Turnstile('secret'))->verify(''));
    }

    public function testAcceptsTokenWhenCloudflareSucceeds(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->assertTrue((new Turnstile('secret'))->verify('token', '1.2.3.4'));
    }

    public function testRejectsTokenWhenCloudflareFails(): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->assertFalse((new Turnstile('secret'))->verify('token'));
    }
}
