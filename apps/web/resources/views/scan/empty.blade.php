<x-scan-layout :title="'No risky approvals · ' . $shortAddress">
    <header class="wt-header wt-header--simple">
        <div class="wt-identity">
            <div class="wt-chiprow">
                <span class="wt-chain-chip"><span class="wt-chain-chip__dot"></span><span class="wt-chain-chip__label">Ethereum · chainId {{ $chainId }}</span></span>
            </div>
            <div class="wt-addr" style="margin-bottom:12px;">{{ $maskedAddress }}</div>
            <div style="font-size:13px;letter-spacing:-.02em;color:var(--wt-sage);">Scanned&nbsp;&nbsp;{{ $scannedAt }} · {{ $result['approvalsReviewed'] }} approvals reviewed</div>
        </div>
        <div class="wt-score-actions">
            <div class="wt-score-col">
                <x-score-gauge :severity="$result['severity']" :score="$result['score']" />
                <span class="wt-badge" style="--bg:#ECFCF2;--bd:#CFE9D9;--fg:#2E4F21;--ic:#2E4F21">
                    <span class="wt-badge__mark">●</span>
                    <span class="wt-badge__label">INFO · Healthy</span>
                </span>
            </div>
            <a href="{{ url('/scan/' . $address) }}" class="wt-btn wt-btn--solid" style="align-self:center;" data-scan-loading data-addr="{{ $address }}">Re-scan</a>
        </div>
    </header>

    <section class="wt-empty">
        <div class="wt-empty__mark"><div class="wt-empty__mark-inner">✓</div></div>
        <h2 class="wt-empty__title">No risky approvals found</h2>
        <p class="wt-empty__p">None of the {{ $result['approvalsReviewed'] }} token approvals we reviewed had unlimited permissions or unverified or malicious spenders.</p>
        <p class="wt-empty__note">Approval status can change over time. Check back periodically.</p>
        <div class="wt-empty__actions">
            <a href="{{ url('/scan/' . $address . '/og') }}" class="wt-btn wt-btn--outline"><span style="font-size:13px;">↗</span> Share result</a>
        </div>

        @if (session('monitorStatus') === 'ok')
            <div class="wt-monitor-ok wt-monitor-block" role="status">
                <div class="wt-monitor-ok__title">You're on the watch list</div>
                <p class="wt-monitor-ok__text">We'll email you if a risky approval appears for this wallet later. Free monitoring is coming soon — no email is sent yet.</p>
            </div>
        @else
            <form class="wt-monitor-block" method="post" action="{{ url('/monitor') }}">
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
                    <span>Get alerted if a risky approval appears later. Unsubscribe anytime.</span>
                </label>
                @error('consent')<div class="wt-form-error">{{ $message }}</div>@enderror
            </form>
        @endif
    </section>

    <div class="wt-share-wrap"><x-share-buttons /></div>
</x-scan-layout>
