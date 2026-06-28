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

    /**
     * GoPlus nft_approval_security v2 의 result(NFT별 operator approved_list) 를 반환. 실패 시 GoPlusException.
     *
     * @return array<int,array<string,mixed>>
     */
    public function nftApprovals(string $address, int $chainId): array;

    /**
     * 직전 호출들이 부분(GoPlus code 2 = 아직 스캐닝) 또는 NFT 한쪽 엔드포인트 실패였는지.
     * true면 데이터가 불완전 → 호출자는 결과를 STALE로 표기해야 한다(거짓 "clean" 방지).
     */
    public function wasPartial(): bool;
}
