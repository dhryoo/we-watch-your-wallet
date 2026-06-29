<x-scan-layout :title="'Scan unavailable · ' . $shortAddress">
    <header class="wt-header wt-header--simple">
        <div class="wt-identity">
            <div class="wt-chiprow">
                <span class="wt-chain-chip"><span class="wt-chain-chip__dot"></span><span class="wt-chain-chip__label">{{ $chainLabel }} · chainId {{ $chainId }}</span></span>
                <span class="wt-readonly">Read-only analysis</span>
            </div>
            <div class="wt-addr">{{ $maskedAddress }}</div>
        </div>
        <a href="{{ url($scanBase) }}" class="wt-btn wt-btn--solid" style="align-self:center;" data-scan-loading data-addr="{{ $address }}">Try again</a>
    </header>

    <section class="wt-body" style="padding-bottom:48px;">
        <div class="wt-stale">
            <span class="wt-stale__dot">●</span>
            <div class="wt-stale__text">We couldn't complete this scan right now — <span class="wt-mono">STALE</span><span class="muted">. The risk data source was unavailable. We don't guess or assume your wallet is safe — please try again in a moment.</span></div>
        </div>
    </section>
</x-scan-layout>
