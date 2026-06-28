<?php

namespace App\Scan;

interface GoPlusGateway
{
    /**
     * GoPlus token_approval_security v2 의 result(토큰별 approved_list) 를 반환. 실패 시 GoPlusException.
     *
     * @return array<int,array<string,mixed>>
     */
    public function approvals(string $address, int $chainId): array;
}
