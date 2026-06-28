<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    public function testApiDocsPageRenders(): void
    {
        $response = $this->get('/api-docs');

        $response->assertOk();
        $response->assertSee('GET');
        $response->assertSee('/api/scan/', false);
        $response->assertSee('CORS');
        $response->assertSee('not financial');
    }
}
