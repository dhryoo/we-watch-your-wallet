<?php

namespace Tests\Feature;

use Tests\TestCase;

class ShareTest extends TestCase
{
    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';

    public function testResultPageHasShareIntents(): void
    {
        $response = $this->get('/scan/' . $this->address . '?demo=1');

        $response->assertOk();
        $response->assertSee('twitter.com/intent/tweet', false);
        $response->assertSee('warpcast.com/~/compose', false);
        // shares the TOOL (home url), not the user's wallet address (premortem #3: avoid negative/address-leaking share)
        $response->assertSee('data-share-copy="' . url('/') . '"', false);
        $response->assertDontSee('data-share-copy="' . url('/scan/' . $this->address) . '"', false);
    }
}
