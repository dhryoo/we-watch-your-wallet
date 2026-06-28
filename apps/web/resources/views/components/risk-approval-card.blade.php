@props(['risk'])
@php $t = \App\Scan\Severity::tokens($risk['severity']); @endphp
<article class="wt-card" style="--bg:{{ $t['bg'] }};--bd:{{ $t['bd'] }};--ic:{{ $t['ic'] }}">
    <div class="wt-card__strip">
        <div class="wt-card__token">
            <div class="wt-token-id">
                <div class="wt-token-avatar">{{ $risk['tokenInit'] }}</div>
                <div>
                    <div class="wt-token-sym">{{ $risk['token'] }}</div>
                    <div class="wt-token-addr">{{ $risk['tokenAddress'] }}</div>
                </div>
            </div>
            <x-spender-tag :tag="$risk['spenderTag']" />
            <span class="wt-eoa">{{ $risk['isContract'] ? 'Contract' : 'EOA' }}</span>
        </div>
        <x-severity-badge :severity="$risk['severity']" />
    </div>
    <div class="wt-card__bodywrap">
        <div class="wt-card__cols">
            <div class="wt-card__left">
                <div class="wt-limit-row">
                    <span class="wt-limit-label">Approval limit</span>
                    @if ($risk['unlimited'])
                        <span class="wt-unlimited">Unlimited</span>
                    @else
                        <span class="wt-limit-text">{{ $risk['limitText'] }}</span>
                    @endif
                    @if ($risk['stale'])
                        <span class="wt-balance-stale">Balance STALE</span>
                    @endif
                </div>
                <div class="wt-signals">
                    @foreach ($risk['signals'] as $signal)
                        <span class="wt-signal"><span class="wt-signal__dot"></span>{{ $signal }}</span>
                    @endforeach
                </div>
                <p class="wt-explain">{{ $risk['explain'] }}</p>
                <div class="wt-why">
                    <div class="wt-why__title">Why it matters</div>
                    <p class="wt-why__text">{{ $risk['why'] }}</p>
                    <div class="wt-checklist">
                        @foreach ($risk['checklist'] as $item)
                            <div class="wt-check"><span class="wt-check__box"></span><span class="wt-check__text">{{ $item }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="wt-card__right">
                <div class="wt-action">
                    <div class="wt-action__label">Recommended check</div>
                    @if (!empty($risk['spenderAddress']))
                        <div class="wt-action__spender">Spender <span class="wt-mono">{{ $risk['spenderAddress'] }}</span></div>
                    @endif
                    <x-revoke-button :url="$risk['revokeUrl']" />
                </div>
            </div>
        </div>
    </div>
</article>
