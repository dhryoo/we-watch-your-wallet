@props([
    'severity',
    'score',
    'size' => 128,
    'dark' => false,
    'scoreSize' => 42,
    'unit' => '/ 100',
    'unitSize' => 10,
])
@php
    $t = \App\Scan\Severity::tokens($severity);
    // 낮은 위험(INFO)은 브랜드 그린 링, 그 외는 severity accent.
    $accent = strtoupper($severity) === 'INFO' ? '#2E4F21' : $t['ic'];
    $track = $dark ? '#3A6029' : '#EFE7E4';
    $hole = $dark ? (int) round($size * 0.79) : $size - 30;
    $holeBg = $dark ? '#2E4F21' : '#FFFFFF';
    $scoreColor = $dark ? '#FFFFFF' : '#2E4F21';
    $clamped = max(0, min(100, (int) $score));
@endphp
<div class="wt-gauge" style="width:{{ $size }}px;height:{{ $size }}px;--accent:{{ $accent }};--track:{{ $track }};--score:{{ $clamped }}">
    <div class="wt-gauge__hole" style="width:{{ $hole }}px;height:{{ $hole }}px;--hole:{{ $holeBg }}"></div>
    <div class="wt-gauge__inner">
        <div class="wt-gauge__score" style="font-size:{{ $scoreSize }}px;--score-color:{{ $scoreColor }}">{{ $score }}</div>
        <div class="wt-gauge__unit" style="font-size:{{ $unitSize }}px">{{ $unit }}</div>
    </div>
</div>
