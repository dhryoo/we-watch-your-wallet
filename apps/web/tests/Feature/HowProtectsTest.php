<?php

namespace Tests\Feature;

use Tests\TestCase;

class HowProtectsTest extends TestCase
{
    public function testHomeShowsHowItProtectsInfographic(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('How we protect your assets');
        $response->assertSee('An approval is a key to your wallet');
        $response->assertSee('We surface the risky keys');
        $response->assertSee('Get warned before harm, not after');
        $response->assertSee('Pro · coming soon', false);
    }
}
