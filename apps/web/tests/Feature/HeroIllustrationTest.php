<?php

namespace Tests\Feature;

use Tests\TestCase;

class HeroIllustrationTest extends TestCase
{
    public function testHomeHeroShowsExampleScanIllustration(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // Two-column hero: the right half holds the example-result illustration.
        $response->assertSee('wt-hero__art');
        // Honesty: it must be clearly labelled as an illustrative example, not live data.
        $response->assertSee('Example scan result', false);
        $response->assertSee('EXAMPLE');
    }
}
