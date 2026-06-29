<?php

namespace Tests\Feature;

use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    public function testHomeHasMetaDescription(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="description"', false)
            ->assertSee('non-custodial', false);
    }

    public function testHomeHasOpenGraphWithDefaultImage(): void
    {
        $response = $this->get('/');

        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:url"', false);
        $response->assertSee('property="og:type"', false);
        $response->assertSee('og-default.png', false); // homepage shares a default brand card
    }

    public function testHomeHasTwitterCard(): void
    {
        $this->get('/')
            ->assertSee('name="twitter:card"', false)
            ->assertSee('summary_large_image', false);
    }

    public function testHomeHasCanonical(): void
    {
        $this->get('/')->assertSee('rel="canonical"', false);
    }

    public function testHomeHasStructuredData(): void
    {
        $this->get('/')
            ->assertSee('application/ld+json', false)
            ->assertSee('WebApplication', false);
    }

    public function testHomeHasThemeColorAndIcons(): void
    {
        $this->get('/')
            ->assertSee('name="theme-color"', false)
            ->assertSee('rel="apple-touch-icon"', false);
    }

    public function testDocPagesHaveMetaDescription(): void
    {
        $this->get('/how-it-works')->assertOk()->assertSee('<meta name="description"', false);
        $this->get('/non-custodial')->assertOk()->assertSee('<meta name="description"', false);
        $this->get('/api-docs')->assertOk()->assertSee('<meta name="description"', false);
    }

    public function testScanResultStillUsesItsOwnPerWalletOgImage(): void
    {
        // regression: the default brand image must NOT override a scan page's specific card.
        $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';
        $this->get('/scan/' . $address . '?demo=1')
            ->assertOk()
            ->assertSee('/scan/' . $address . '/og-image', false)
            ->assertDontSee('og-default.png', false);
    }
}
