<x-scan-layout :title="'Wallet risk scan · ' . $shortAddress" :ogImage="url('/scan/' . $address . '/og')" ogDescription="Includes an unlimited USDC approval. Review and revoke yourself if needed.">
    <header class="wt-header">
        <div class="wt-header__row">
            <div class="wt-identity">
                <div class="wt-chiprow">
                    <span class="wt-chain-chip"><span class="wt-chain-chip__dot"></span><span class="wt-chain-chip__label">Ethereum · chainId {{ $chainId }}</span></span>
                    <span class="wt-readonly">Read-only analysis</span>
                </div>
                <div class="wt-addr-row">
                    <span class="wt-addr" id="wt-addr" data-full="{{ $address }}" data-masked="{{ $maskedAddress }}">{{ $maskedAddress }}</span>
                    <button type="button" class="wt-toggle" id="wt-addr-toggle" data-state="masked"><span>Show address</span></button>
                </div>
                <div class="wt-meta">
                    <span>Scanned&nbsp;&nbsp;{{ $scannedAt }}</span>
                    <span class="wt-meta__sep"></span>
                    <span>{{ $result['approvalsReviewed'] }} approvals reviewed · {{ count($result['risks']) }} risky</span>
                </div>
            </div>
            <div class="wt-score-actions">
                <div class="wt-score-col">
                    <x-score-gauge :severity="$result['severity']" :score="$result['score']" />
                    <x-severity-badge :severity="$result['severity']" />
                </div>
                <div class="wt-actions-col">
                    <a href="{{ url('/scan/' . $address) }}" class="wt-btn wt-btn--solid" data-scan-loading data-addr="{{ $address }}">Re-scan</a>
                    <a href="{{ url('/scan/' . $address . '/og') }}" class="wt-btn wt-btn--outline"><span style="font-size:13px;">↗</span> Share result</a>
                </div>
            </div>
        </div>
        @if ($result['stale'])
            <div class="wt-stale">
                <span class="wt-stale__dot">●</span>
                <div class="wt-stale__text">Some price and balance data couldn't be fetched — <span class="wt-mono">STALE</span><span class="muted">. Affected items are marked. Approval permission analysis is current.</span></div>
            </div>
        @endif
    </header>

    <section class="wt-body">
        <div class="wt-body__head">
            <h2 class="wt-h2"><span class="num">{{ count($result['risks']) }}</span> risky token approvals</h2>
            <span class="wt-sort">Sorted by risk, highest first</span>
        </div>
        <div class="wt-cards">
            @foreach ($result['risks'] as $risk)
                <x-risk-approval-card :risk="$risk" />
            @endforeach
        </div>
    </section>

    <section class="wt-cta">
        <div class="wt-cta__copy">
            <h3 class="wt-cta__h3">Keep watching this wallet</h3>
            <p class="wt-cta__p">We'll email you when a new risky approval is detected. We never ask for your keys or funds — read-only monitoring by address.</p>
        </div>
        @if (session('monitorStatus') === 'ok')
            <div class="wt-cta__form wt-monitor-ok" role="status">
                <div class="wt-monitor-ok__title">You're on the watch list</div>
                <p class="wt-monitor-ok__text">We'll email you when we detect a new risky approval for this wallet. Pro monitoring is coming soon — no email is sent yet.</p>
            </div>
        @else
            <form class="wt-cta__form" method="post" action="{{ url('/monitor') }}">
                @csrf
                <input type="hidden" name="source" value="result">
                <input type="hidden" name="wallet_address" value="{{ $address }}">
                <input type="hidden" name="scanned_wallet" value="{{ $address }}">
                <input type="hidden" name="scan_severity" value="{{ $result['severity'] }}">
                @error('monitor')<div class="wt-form-error" style="margin:0 0 10px;">{{ $message }}</div>@enderror
                {{-- honeypot: a real person leaves this empty --}}
                <input type="text" name="website" class="wt-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <div class="wt-cta__inputrow">
                    <input class="wt-input" type="email" name="email" value="{{ old('email') }}" placeholder="you@email.com" aria-label="Email address">
                    <button class="wt-btn wt-btn--solid" type="submit">Monitor</button>
                </div>
                @error('email')<div class="wt-form-error">{{ $message }}</div>@enderror
                <label class="wt-consent">
                    <input type="checkbox" name="consent" value="1" @checked(old('consent'))>
                    <span>By submitting, you agree we may email you about wallet monitoring. Unsubscribe anytime.</span>
                </label>
                @error('consent')<div class="wt-form-error">{{ $message }}</div>@enderror
            </form>
        @endif
    </section>

    <script>
        (function () {
            var btn = document.getElementById('wt-addr-toggle');
            var addr = document.getElementById('wt-addr');
            if (!btn || !addr) { return; }
            btn.addEventListener('click', function () {
                var masked = btn.getAttribute('data-state') === 'masked';
                addr.textContent = masked ? addr.getAttribute('data-full') : addr.getAttribute('data-masked');
                btn.querySelector('span').textContent = masked ? 'Mask' : 'Show address';
                btn.setAttribute('data-state', masked ? 'full' : 'masked');
            });
        })();
    </script>
</x-scan-layout>
