<?php

namespace App\Scan;

use InvalidArgumentException;

/**
 * 위험 등급(severity) 디자인 토큰과 정렬 순위.
 * 색만으로 구분 금지 — 항상 mark + label 동반(접근성). design README SeverityBadge 표.
 */
class Severity
{
    private const TOKENS = [
        'INFO' => ['mark' => '●', 'label' => 'INFO', 'fg' => '#3F5660', 'bg' => '#EEF3F5', 'bd' => '#C3CED4', 'ic' => '#5B7280'],
        'WATCH' => ['mark' => '●', 'label' => 'WATCH', 'fg' => '#7A5E10', 'bg' => '#FBF5E3', 'bd' => '#E8D9A8', 'ic' => '#C39A2C'],
        'WARN' => ['mark' => '◆', 'label' => 'WARN', 'fg' => '#8E4419', 'bg' => '#FBEFE7', 'bd' => '#EFCBB2', 'ic' => '#C2622E'],
        'URGENT' => ['mark' => '▲', 'label' => 'URGENT', 'fg' => '#8E2018', 'bg' => '#FBEAE8', 'bd' => '#EFB5B0', 'ic' => '#B3382E'],
    ];

    private const RANK = ['INFO' => 0, 'WATCH' => 1, 'WARN' => 2, 'URGENT' => 3];

    /** @return array{mark:string,label:string,fg:string,bg:string,bd:string,ic:string} */
    public static function tokens(string $severity): array
    {
        $key = strtoupper($severity);

        if (!isset(self::TOKENS[$key]))
        {
            throw new InvalidArgumentException("Unknown severity: {$severity}");
        }

        return self::TOKENS[$key];
    }

    public static function rank(string $severity): int
    {
        return self::RANK[strtoupper($severity)] ?? -1;
    }
}
