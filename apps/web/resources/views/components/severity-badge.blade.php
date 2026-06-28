@props(['severity'])
@php $t = \App\Scan\Severity::tokens($severity); @endphp
<span class="wt-badge" style="--fg:{{ $t['fg'] }};--bg:{{ $t['bg'] }};--bd:{{ $t['bd'] }};--ic:{{ $t['ic'] }}">
    <span class="wt-badge__mark">{{ $t['mark'] }}</span>
    <span class="wt-badge__label">{{ $t['label'] }}</span>
</span>
