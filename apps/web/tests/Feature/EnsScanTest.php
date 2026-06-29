<?php

namespace Tests\Feature;

use App\Scan\EnsResolver;
use App\Scan\FakeEnsResolver;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EnsScanTest extends TestCase
{
    private const VITALIK = '0xd8da6bf26964af9d7eed9e03e53415d37aa96045';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function bindEns(array $map = [], bool $fail = false): FakeEnsResolver
    {
        $resolver = new FakeEnsResolver($map, $fail);
        $this->instance(EnsResolver::class, $resolver);

        return $resolver;
    }

    public function testEnsNameResolvesAndRedirectsToScan(): void
    {
        $this->bindEns(['vitalik.eth' => self::VITALIK]);

        $this->post('/scan', ['address' => 'vitalik.eth'])
            ->assertRedirect('/scan/' . self::VITALIK);
    }

    public function testUnresolvableEnsNameShowsFriendlyError(): void
    {
        $this->bindEns([]); // 매핑 없음 → null(주소 레코드 없음)

        $this->post('/scan', ['address' => 'nope.eth'])
            ->assertSessionHasErrors('address');
    }

    public function testEnsRpcFailureShowsRetryErrorNotFakeResult(): void
    {
        $this->bindEns([], fail: true);

        $this->post('/scan', ['address' => 'vitalik.eth'])
            ->assertSessionHasErrors('address');
    }

    public function testMalformedInputErrorsWithoutAttemptingResolution(): void
    {
        $resolver = $this->bindEns([]);

        $this->post('/scan', ['address' => 'garbage'])
            ->assertSessionHasErrors('address');

        // 0x도 ENS 이름꼴도 아니면 해석을 시도하지 않는다.
        $this->assertSame(0, $resolver->calls);
    }

    public function testHomeInvitesEnsNames(): void
    {
        // 서버가 ENS를 받으므로 입력 어포던스도 그 사실을 알려야 한다.
        $this->get('/')
            ->assertOk()
            ->assertSee('ENS name')
            ->assertSee('vitalik.eth');
    }

    public function testResolvedEnsAddressIsCachedAcrossRequests(): void
    {
        $resolver = $this->bindEns(['vitalik.eth' => self::VITALIK]);

        $this->post('/scan', ['address' => 'vitalik.eth']);
        $this->post('/scan', ['address' => 'VITALIK.ETH']); // 정규화 동일 키 → 캐시 히트

        $this->assertSame(1, $resolver->calls);
    }
}
