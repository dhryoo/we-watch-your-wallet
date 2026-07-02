<footer class="wt-footer">
    <div class="wt-footer__brand">
        <div class="wt-footer__logo"><div></div></div>
        <span class="wt-footer__name">We Watch Your Wallet</span>
    </div>
    <nav class="wt-footer__links">
        <a href="{{ url('/how-it-works') }}">How it works</a>
        <a href="{{ url('/learn') }}">Learn</a>
        <a href="{{ url('/non-custodial') }}">Non-custodial</a>
        <a href="{{ url('/api-docs') }}">API</a>
        @if ($repo = config('scan.repo_url'))<a href="{{ $repo }}" target="_blank" rel="noopener noreferrer">Open source ↗</a>@endif
    </nav>
    <p class="wt-disclaimer">We Watch Your Wallet is a non-custodial risk monitoring service. We never access your private keys or funds; we analyze public on-chain data read-only. These results are informational only and are not financial, investment, or legal advice. We do not recommend buying, selling, or trading. All transactions (including revoking approvals) must be reviewed and executed by you in your own wallet. Data may be out of date, in which case we mark it STALE.</p>
</footer>
