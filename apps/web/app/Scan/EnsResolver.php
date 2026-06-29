<?php

namespace App\Scan;

/**
 * ENS 이름 → 0x 주소 해석. 정직성: 일시적 실패는 EnsException을 던지고(거짓 결과 금지),
 * 정상적으로 "주소 레코드 없음"일 때만 null을 반환한다.
 */
interface EnsResolver
{
    public function resolve(string $name): ?string;
}
