<?php

namespace Tests\Feature;

use App\Scan\FakeGoPlusGateway;
use App\Scan\GoPlusGateway;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 폰트 셀프호스팅 — "무추적" 약속 일관성. Google Fonts 로드는 방문자 IP를
 * 제3자(구글)에 노출하므로 어떤 페이지도 외부 폰트 호스트를 참조하면 안 된다.
 */
class FontSelfHostTest extends TestCase
{
    public function testHomeDoesNotLoadThirdPartyFonts(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('fonts.googleapis.com', false);
        $response->assertDontSee('fonts.gstatic.com', false);
    }

    public function testHomeLoadsSelfHostedFontsCss(): void
    {
        $this->get('/')->assertSee('css/fonts.css', false);
    }

    public function testOgSharePreviewAlsoSelfHosted(): void
    {
        Cache::flush();
        $this->instance(GoPlusGateway::class, new FakeGoPlusGateway([]));

        $response = $this->get('/scan/0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D/og');

        $response->assertOk();
        $response->assertDontSee('fonts.googleapis.com', false);
        $response->assertDontSee('fonts.gstatic.com', false);
    }

    public function testFontsCssReferencesOnlyLocalExistingFiles(): void
    {
        $css = public_path('css/fonts.css');
        $this->assertFileExists($css);

        $content = (string) file_get_contents($css);
        $this->assertStringNotContainsString('http', $content); // 완전 셀프호스팅(외부 URL 0)

        preg_match_all('/url\((["\']?)([^)"\']+)\1\)/', $content, $m);
        $this->assertNotEmpty($m[2], 'fonts.css must reference at least one font file');

        foreach ($m[2] as $url)
        {
            $this->assertStringStartsWith('/fonts/', $url);
            $this->assertFileExists(public_path(ltrim($url, '/')));
        }
    }
}
