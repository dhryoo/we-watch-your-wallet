<?php

namespace App\Scan;

/**
 * 테스트·데모용 GoPlus 게이트웨이. 실 API 호출 없이 고정 fixture 반환(또는 실패 시뮬레이션).
 */
class FakeGoPlusGateway implements GoPlusGateway
{
    /** 실제 호출 횟수(캐시 히트 검증용). */
    public int $calls = 0;

    /** @param array<int,array<string,mixed>> $result */
    public function __construct(
        private readonly array $result = [],
        private readonly bool $shouldFail = false
    )
    {
    }

    public function approvals(string $address, int $chainId): array
    {
        $this->calls++;

        if ($this->shouldFail)
        {
            throw new GoPlusException('fake gateway failure');
        }

        return $this->result;
    }
}
