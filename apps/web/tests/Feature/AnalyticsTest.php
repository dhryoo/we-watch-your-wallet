<?php

namespace Tests\Feature;

use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    public function testCookielessAnalyticsRenderedWhenTokenConfigured(): void
    {
        config(['scan.analytics.cf_token' => 'beacontoken123']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('static.cloudflareinsights.com/beacon.min.js', false);
        $response->assertSee('beacontoken123', false);
    }

    public function testNoAnalyticsWhenTokenMissing(): void
    {
        config(['scan.analytics.cf_token' => null]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('cloudflareinsights', false);
    }
}
