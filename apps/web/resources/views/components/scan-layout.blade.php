@props([
    'title' => 'Wallet risk scan',
    'description' => "Free, non-custodial, read-only Ethereum wallet token-approval risk scanner. Spot unlimited, unverified, or malicious approvals across 5 chains and revoke them yourself.",
    'ogImage' => null,
    'ogDescription' => null,
])
@php
    $fullTitle = $title . ' · We Watch Your Wallet';
    $ogDesc = $ogDescription ?? $description;
    $ogImg = $ogImage ?? asset('og-default.png'); // 페이지별 카드 없으면 브랜드 기본 카드
    $canonical = url()->current();
    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'WebApplication',
        'name' => 'We Watch Your Wallet',
        'url' => url('/'),
        'applicationCategory' => 'SecurityApplication',
        'operatingSystem' => 'Web',
        'description' => $description,
        'isAccessibleForFree' => true,
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="theme-color" content="#2E4F21">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@200;300;400;500;600&family=DM+Sans:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/watchtower.css') }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="We Watch Your Wallet">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImg }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $ogDesc }}">
    <meta name="twitter:image" content="{{ $ogImg }}">

    <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
<body>
    <main class="wt-page">
        <x-nav />
        {{ $slot }}
        <x-site-footer />
    </main>

    {{-- Scan loading overlay (shown on submit/re-scan; replaced when the result page loads) --}}
    <div class="wt-loading" id="wt-loading" role="status" aria-live="polite" aria-hidden="true">
        <div class="wt-loading__card">
            <div class="wt-loading__spinner" aria-hidden="true"></div>
            <div class="wt-loading__title">Reading on-chain approvals…</div>
            <div class="wt-loading__sub">Checking spenders and scoring risk — this can take a few seconds. Read-only, non-custodial.</div>
            <div class="wt-loading__addr" id="wt-loading-addr" hidden></div>
        </div>
    </div>

    <script src="{{ asset('js/scan-loading.js') }}"></script>

    @if ($cfToken = config('scan.analytics.cf_token'))
        {{-- Cloudflare Web Analytics — cookieless, no PII (privacy-respecting, fits our no-tracking promise) --}}
        <script defer src="https://static.cloudflareinsights.com/beacon.min.js" data-cf-beacon='{"token": "{{ $cfToken }}"}'></script>
    @endif
</body>
</html>
