<?php

namespace Tests\Feature;

use Tests\TestCase;

class NavBrandTest extends TestCase
{
    public function testNavBrandLinksToHome(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // The top-left logo + name is a link back to the home page.
        $response->assertSee('<a href="/" class="wt-nav__brand">', false);
    }
}
