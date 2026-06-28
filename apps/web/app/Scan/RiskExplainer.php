<?php

namespace App\Scan;

/**
 * 한 건의 위험한 approval에 대한 평문 설명 생성기.
 * 구현은 입력 facts에만 근거(그라운딩)하고 비자문이어야 하며, 신뢰할 수 없으면 null을 반환해
 * 호출자(GoPlusScanner)가 안전한 템플릿 설명으로 폴백하게 한다.
 */
interface RiskExplainer
{
    /**
     * @param array<string,mixed> $facts 토큰·평판·무제한 여부 등 그라운딩된 사실만.
     * @return string|null 평문 설명, 또는 신뢰 불가 시 null(템플릿 폴백).
     */
    public function explain(array $facts): ?string;
}
