<?php

namespace Tests\Feature;

use Tests\TestCase;

class LearnPagesTest extends TestCase
{
    /** @var list<string> */
    private array $slugs = [
        'what-is-a-token-approval',
        'how-to-revoke-token-approvals',
        'unlimited-token-approvals',
        'approval-phishing',
    ];

    public function testLearnIndexListsAllArticles(): void
    {
        $response = $this->get('/learn');

        $response->assertOk();
        $response->assertSee('<meta name="description"', false);

        foreach ($this->slugs as $slug)
        {
            $response->assertSee('/learn/' . $slug, false);
        }
    }

    public function testEachArticleRendersWithMetaAndScanCta(): void
    {
        foreach ($this->slugs as $slug)
        {
            $response = $this->get('/learn/' . $slug);

            $response->assertOk();
            $response->assertSee('<h1>', false);
            $response->assertSee('<meta name="description"', false);
            // 모든 교육 페이지는 스캐너로의 CTA를 갖는다(검색 유입 → 스캔 전환).
            $response->assertSee('Scan a wallet', false);
            // 교차 링크(내부 링크 그물) — 다른 learn 글로 연결.
            $response->assertSee('/learn/', false);
        }
    }

    public function testSitemapIncludesLearnPages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee(url('/learn'), false);

        foreach ($this->slugs as $slug)
        {
            $response->assertSee(url('/learn/' . $slug), false);
        }
    }

    public function testFooterLinksToLearn(): void
    {
        $this->get('/')->assertSee('href="' . url('/learn') . '"', false);
    }
}
