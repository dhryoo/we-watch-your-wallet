<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * SEO 정본 호스트 통합 — Search Console "중복 페이지(사용자 선택 표준 없음)" 해결.
 * www.* 는 apex로 301, canonical/og:url은 apex 절대 URL로 고정한다.
 */
class CanonicalHostTest extends TestCase
{
    public function testWwwRedirectsToApexWith301(): void
    {
        $response = $this->get('https://www.wewatchyourwallet.com/learn');

        $response->assertStatus(301);
        $response->assertRedirect('https://wewatchyourwallet.com/learn');
    }

    public function testWwwRedirectPreservesPathAndQuery(): void
    {
        $this->get('https://www.wewatchyourwallet.com/how-it-works?x=1')
            ->assertStatus(301)
            ->assertRedirect('https://wewatchyourwallet.com/how-it-works?x=1');
    }

    public function testApexIsNotRedirected(): void
    {
        $this->get('https://wewatchyourwallet.com/learn')->assertOk();
    }

    public function testCanonicalNeverContainsWww(): void
    {
        // 방어: 어떤 이유로든 www 로 렌더되더라도 canonical/og:url 은 apex 를 가리켜야 한다.
        $html = $this->get('https://wewatchyourwallet.com/')->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="https://wewatchyourwallet.com', $html);
        $this->assertStringNotContainsString('https://www.', $html);
    }
}
