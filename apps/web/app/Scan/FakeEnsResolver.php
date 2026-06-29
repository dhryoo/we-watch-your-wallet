<?php

namespace App\Scan;

/** 테스트용 ENS 리졸버. 고정 map 반환 또는 실패 시뮬레이션. */
class FakeEnsResolver implements EnsResolver
{
    /** 해석 호출 횟수(캐시 검증용). */
    public int $calls = 0;

    /**
     * @param array<string,string> $map name(소문자) → 0x 주소
     */
    public function __construct(
        private readonly array $map = [],
        private readonly bool $shouldFail = false,
    ) {
    }

    public function resolve(string $name): ?string
    {
        $this->calls++;

        if ($this->shouldFail)
        {
            throw new EnsException('fake ens failure');
        }

        return $this->map[strtolower(trim($name))] ?? null;
    }
}
