<?php

namespace Tests\Feature;

use Tests\TestCase;

class ScanValidateTest extends TestCase
{
    public function testHomeIncludesClientSideAddressValidation(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        // Inline error target shown when the address is invalid (no full-page round-trip).
        $response->assertSee('id="wt-address-error"', false);
        // The progressive-enhancement validator that blocks an invalid submit before it reloads.
        $response->assertSee('js/scan-validate.js', false);
    }
}
