<?php

namespace Tests\Feature;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    public function testSitemapListsPublicPagesNotScanResults(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(url('/'), false);
        $response->assertSee(url('/how-it-works'), false);
        $response->assertSee(url('/non-custodial'), false);
        $response->assertSee(url('/api-docs'), false);
        // per-wallet scan pages are robots-disallowed → must not be advertised in the sitemap
        $response->assertDontSee('/scan/', false);
    }
}
