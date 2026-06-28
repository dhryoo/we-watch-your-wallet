<?php

namespace App\Scan;

/**
 * 스캔 결과 view-model 헬퍼.
 */
class ScanResult
{
    /**
     * 위험 카드를 severity 내림차순(URGENT→WARN→WATCH→INFO)으로 정렬. 동순위는 입력 순서 유지(안정 정렬).
     *
     * @param  array<int,array<string,mixed>>  $risks  각 항목은 'severity' 키를 가진다.
     * @return array<int,array<string,mixed>>
     */
    public static function sortBySeverityDesc(array $risks): array
    {
        usort(
            $risks,
            static fn (array $a, array $b): int => Severity::rank($b['severity']) <=> Severity::rank($a['severity'])
        );

        return $risks;
    }
}
