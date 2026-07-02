<nav class="wt-nav">
    <a href="/" class="wt-nav__brand">
        <div class="wt-logo"><div class="wt-logo__dot"></div></div>
        <span class="wt-nav__name">We Watch Your Wallet</span>
    </a>
    <div class="wt-nav__links">
        <a href="{{ url('/how-it-works') }}" class="wt-nav__link">How it works</a>
        <a href="{{ url('/learn') }}" class="wt-nav__link">Learn</a>
        <a href="{{ url('/non-custodial') }}" class="wt-nav__link">Non-custodial</a>
        @if ($repo = config('scan.repo_url'))
            <a href="{{ $repo }}" target="_blank" rel="noopener noreferrer" class="wt-pill-dark">Open source ↗</a>
        @endif
    </div>
</nav>
