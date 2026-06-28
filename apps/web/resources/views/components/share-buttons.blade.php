@props(['url' => null])
@php
    $shareUrl = $url ?? url('/');
    $shareText = 'Check your wallet for risky token approvals — free & non-custodial.';
@endphp
<div class="wt-share">
    <span class="wt-share__label">Share</span>
    <a class="wt-share__btn" href="https://twitter.com/intent/tweet?text={{ rawurlencode($shareText) }}&url={{ rawurlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer">Post on X</a>
    <a class="wt-share__btn" href="https://warpcast.com/~/compose?text={{ rawurlencode($shareText . ' ' . $shareUrl) }}" target="_blank" rel="noopener noreferrer">Farcaster</a>
    <button type="button" class="wt-share__btn" data-share-copy="{{ $shareUrl }}">Copy link</button>
</div>
@once
    <script src="{{ asset('js/share.js') }}"></script>
@endonce
