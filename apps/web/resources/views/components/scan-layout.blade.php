@props(['title' => 'Wallet risk scan', 'ogImage' => null, 'ogDescription' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · We Watch Your Wallet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@200;300;400;500;600&family=DM+Sans:wght@400;500;600&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/watchtower.css') }}">
    @if ($ogImage)
        <meta property="og:title" content="{{ $title }}">
        @if ($ogDescription)<meta property="og:description" content="{{ $ogDescription }}">@endif
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
    @endif
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
