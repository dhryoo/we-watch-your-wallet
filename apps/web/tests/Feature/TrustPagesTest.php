<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustPagesTest extends TestCase
{
    public function testHowItWorksPageRenders(): void
    {
        $response = $this->get('/how-it-works');

        $response->assertOk();
        $response->assertSee('How it works');
        $response->assertSee('read-only');
    }

    public function testNonCustodialPageRenders(): void
    {
        $response = $this->get('/non-custodial');

        $response->assertOk();
        $response->assertSee('non-custodial', false);
        $response->assertSee('What we never do');
    }

    public function testNavLinksTrustPagesAndHasNoDeadInformationalLinks(): void
    {
        $response = $this->get('/');

        $response->assertSee('href="' . url('/how-it-works') . '"', false);
        $response->assertSee('href="' . url('/non-custodial') . '"', false);
        // the informational nav links must no longer be dead "#" anchors
        $response->assertDontSee('href="#" class="wt-nav__link"', false);
    }
}
