<?php

namespace App\Scan;

/**
 * LLM 비활성(키 미설정 등) 시 바인딩되는 no-op 설명기 — 항상 null을 반환해
 * GoPlusScanner가 템플릿 설명을 쓰게 한다(1주차 기본 동작).
 */
class NullRiskExplainer implements RiskExplainer
{
    public function explain(array $facts): ?string
    {
        return null;
    }
}
