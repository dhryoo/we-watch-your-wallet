@props(['tag'])
@php
    $map = [
        'MALICIOUS' => ['bg' => '#FBEAE8', 'bd' => '#EFB5B0', 'fg' => '#8E2018'],
        'UNVERIFIED' => ['bg' => '#FBF5E3', 'bd' => '#E8D9A8', 'fg' => '#7A5E10'],
        'VERIFIED' => ['bg' => '#ECFCF2', 'bd' => '#CFE9D9', 'fg' => '#2E4F21'],
    ];
    $key = strtoupper($tag);
    $s = $map[$key] ?? $map['UNVERIFIED'];
@endphp
<span class="wt-spender" style="--bg:{{ $s['bg'] }};--bd:{{ $s['bd'] }};--fg:{{ $s['fg'] }}">{{ $key }}</span>
