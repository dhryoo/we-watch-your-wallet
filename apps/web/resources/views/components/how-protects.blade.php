{{-- "How we protect your assets" — 3-panel infographic, hand-built inline SVG on the brand palette. --}}
<section class="wt-how" aria-labelledby="wt-how-title">
    <div class="wt-how__head">
        <div class="wt-how__kicker">Non-custodial · read-only</div>
        <h2 id="wt-how-title" class="wt-how__h2">How we protect your assets</h2>
    </div>

    <div class="wt-how__grid">

        {{-- Panel 1 — THE RISK --}}
        <article class="wt-how__panel">
            <svg class="wt-how__art" viewBox="0 0 380 220" role="img" aria-label="An approval is a key to your wallet that can be used to drain it later" fill="none" stroke-linecap="round" stroke-linejoin="round">
                {{-- faint watchtower behind --}}
                <g opacity="0.10" stroke="#2E4F21" stroke-width="3" transform="translate(150,8)">
                    <path d="M4 22 L23 8 L42 22"/><rect x="9" y="22" width="28" height="16" rx="2"/><path d="M19 28 h8"/>
                    <path d="M13 38 L6 70 M33 38 L40 70 M18 38 L15 70 M28 38 L31 70 M9 52 H37 M11 62 H35"/>
                </g>
                {{-- wallet with coins --}}
                <rect x="26" y="74" width="104" height="86" rx="16" fill="#fff" stroke="#2E4F21" stroke-width="2.5"/>
                <path d="M26 100 H130" stroke="#2E4F21" stroke-width="2.5"/>
                <circle cx="112" cy="117" r="6" fill="#A0F1BD" stroke="#2E4F21" stroke-width="2.5"/>
                <g stroke="#2E4F21" stroke-width="2" fill="#ECFCF2">
                    <ellipse cx="62" cy="140" rx="20" ry="6.5"/><ellipse cx="62" cy="131" rx="20" ry="6.5"/><ellipse cx="62" cy="122" rx="20" ry="6.5"/>
                </g>
                {{-- connecting lines --}}
                <path d="M130 117 H162 M225 117 H252" stroke="#7D9276" stroke-width="2"/>
                {{-- amber key (unlimited) --}}
                <g stroke="#C39A2C" stroke-width="2.5" fill="#FBF5E3">
                    <circle cx="178" cy="117" r="14"/><path d="M192 117 H226" stroke-width="4"/><path d="M214 117 v9 M223 117 v6"/>
                </g>
                <g fill="none" stroke="#8E6B16" stroke-width="1.5"><circle cx="174" cy="117" r="2.7"/><circle cx="181" cy="117" r="2.7"/></g>
                <rect x="171.5" y="83.5" width="13" height="13" rx="2" transform="rotate(45 178 90)" fill="#C39A2C"/>
                {{-- unknown contract tile --}}
                <rect x="252" y="82" width="66" height="84" rx="10" fill="#fff" stroke="#3C4A36" stroke-width="2.5"/>
                <g stroke="#7D9276" stroke-width="5"><path d="M266 108 H304"/><path d="M266 122 H298"/><path d="M266 136 H306"/></g>
                {{-- time gap -> later drain --}}
                <path d="M306 90 Q356 66 356 126" stroke="#C7C7C7" stroke-width="2" stroke-dasharray="1.5 5"/>
                <g transform="translate(338,70)"><circle r="9" fill="#fff" stroke="#7D9276" stroke-width="2"/><path d="M0 0 V-5 M0 0 h4" stroke="#7D9276" stroke-width="1.6"/></g>
                <g transform="translate(356,140)"><path d="M0 -12 L12 9 H-12 Z" fill="#B3382E"/><path d="M0 -5 V2" stroke="#fff" stroke-width="2"/><circle cy="6" r="1.5" fill="#fff"/></g>
            </svg>
            <div class="wt-how__step"><span class="wt-how__num">01</span> · The risk</div>
            <h3 class="wt-how__title">An approval is a key to your wallet</h3>
            <p class="wt-how__body">To use a token, you grant a contract permission to move it. A malicious or <strong>unlimited</strong> approval can drain your wallet <em>later</em> — at any time.</p>
        </article>

        {{-- Panel 2 — HOW WE PROTECT --}}
        <article class="wt-how__panel">
            <svg class="wt-how__art" viewBox="0 0 380 220" role="img" aria-label="We scan your approvals, flag the dangerous one, and you revoke it yourself" fill="none" stroke-linecap="round" stroke-linejoin="round">
                {{-- scan beam --}}
                <path d="M186 56 L40 150 L340 150 Z" fill="#ECFCF2" opacity="0.6"/>
                {{-- watchtower --}}
                <g stroke="#2E4F21" stroke-width="2.5" transform="translate(163,6)">
                    <path d="M4 22 L23 8 L42 22"/><rect x="9" y="22" width="28" height="16" rx="2" fill="#fff"/>
                    <circle cx="23" cy="30" r="3.5" fill="#A0F1BD"/>
                    <path d="M13 38 L6 70 M33 38 L40 70 M18 38 L15 70 M28 38 L31 70 M9 52 H37 M11 62 H35"/>
                    <circle cx="23" cy="4" r="3" fill="#A0F1BD" stroke="none"/>
                </g>
                {{-- approval cards: 3 safe + 1 flagged --}}
                @php
                    $cardX = [40, 117, 271];
                @endphp
                @foreach ($cardX as $cx)
                    <rect x="{{ $cx }}" y="150" width="56" height="58" rx="10" fill="#ECFCF2" stroke="#2E4F21" stroke-width="2.5"/>
                    <path d="M{{ $cx + 18 }} 177 l5 6 l11 -13" stroke="#2E4F21" stroke-width="2.5"/>
                @endforeach
                {{-- flagged card --}}
                <rect x="194" y="150" width="56" height="58" rx="10" fill="#fff" stroke="#C39A2C" stroke-width="2.5"/>
                <g fill="none" stroke="#A9520F" stroke-width="1.6" transform="translate(222,176)"><circle cx="-4" r="3"/><circle cx="4" r="3"/></g>
                <rect x="240" y="142" width="15" height="15" rx="2" transform="rotate(45 247.5 149.5)" fill="#C39A2C"/>
                <circle cx="247" cy="149.5" r="2" fill="#B3382E"/>
                {{-- revoke pill on the flagged card --}}
                <g transform="translate(222,196)">
                    <rect x="-26" y="-9" width="52" height="18" rx="9" fill="#2E4F21"/>
                    <g stroke="#A0F1BD" stroke-width="1.8" transform="translate(-16,0)"><path d="M-4 -2 a4 4 0 1 1 -1.2 4.6"/><path d="M-4 -2 l-0.5 -3.5 M-4 -2 l3.4 0.6"/></g>
                    <rect x="-7" y="-3.5" width="22" height="3" rx="1.5" fill="#A0F1BD"/><rect x="-7" y="1.5" width="15" height="3" rx="1.5" fill="#A0F1BD"/>
                </g>
            </svg>
            <div class="wt-how__step"><span class="wt-how__num">02</span> · How we protect</div>
            <h3 class="wt-how__title">We surface the risky keys — before they're used</h3>
            <p class="wt-how__body">We scan your existing approvals and flag the dangerous ones, so you can <strong>revoke</strong> them in your own wallet. Read-only and non-custodial — we never touch your keys or funds.</p>
        </article>

        {{-- Panel 3 — THE VISION --}}
        <article class="wt-how__panel">
            <svg class="wt-how__art" viewBox="0 0 380 220" role="img" aria-label="Continuous monitoring warns you the moment a new risky approval appears, by email" fill="none" stroke-linecap="round" stroke-linejoin="round">
                {{-- radar rings over a wallet constellation --}}
                <g stroke="#A0F1BD" stroke-width="2" fill="none">
                    <circle cx="150" cy="150" r="34" opacity="0.9"/><circle cx="150" cy="150" r="62" opacity="0.55"/><circle cx="150" cy="150" r="90" opacity="0.3"/>
                </g>
                <path d="M150 150 L222 104" stroke="#A0F1BD" stroke-width="2.5"/>
                {{-- safe nodes --}}
                <g fill="#ECFCF2" stroke="#2E4F21" stroke-width="2.5">
                    <circle cx="150" cy="150" r="13"/><circle cx="104" cy="178" r="9"/><circle cx="196" cy="184" r="9"/><circle cx="92" cy="124" r="9"/>
                </g>
                {{-- new risky node caught at the sweep edge --}}
                <circle cx="222" cy="104" r="11" fill="#FBF5E3" stroke="#C39A2C" stroke-width="2.5"/>
                <rect x="216" y="98" width="12" height="12" rx="2" transform="rotate(45 222 104)" fill="#C39A2C"/>
                {{-- watchtower --}}
                <g stroke="#2E4F21" stroke-width="2.5" transform="translate(34,118)">
                    <path d="M3 16 L17 6 L31 16"/><rect x="7" y="16" width="20" height="12" rx="2" fill="#fff"/><circle cx="17" cy="22" r="2.6" fill="#A0F1BD"/>
                    <path d="M10 28 L5 56 M24 28 L29 56 M14 28 L12 56 M20 28 L22 56 M7 40 H27 M9 49 H25"/>
                </g>
                {{-- alert signal to channels --}}
                <path d="M232 100 Q300 60 312 56" stroke="#A0F1BD" stroke-width="2" stroke-dasharray="2 4"/>
                {{-- telegram (paper plane) chip --}}
                <g transform="translate(300,40)">
                    <rect x="-22" y="-15" width="44" height="30" rx="9" fill="#ECFCF2" stroke="#2E4F21" stroke-width="2.5"/>
                    <path d="M-9 0 L9 -7 L3 9 L-1 2 Z" fill="none" stroke="#2E4F21" stroke-width="2"/>
                    <circle cx="14" cy="-11" r="3.5" fill="#A0F1BD"/>
                </g>
                {{-- email chip --}}
                <g transform="translate(338,92)">
                    <rect x="-22" y="-15" width="44" height="30" rx="9" fill="#ECFCF2" stroke="#2E4F21" stroke-width="2.5"/>
                    <rect x="-11" y="-6" width="22" height="14" rx="2.5" fill="none" stroke="#2E4F21" stroke-width="2"/>
                    <path d="M-11 -4 L0 4 L11 -4" stroke="#2E4F21" stroke-width="2" fill="none"/>
                    <circle cx="14" cy="-11" r="3.5" fill="#A0F1BD"/>
                </g>
            </svg>
            <div class="wt-how__step"><span class="wt-how__num">03</span> · Always watching</div>
            <h3 class="wt-how__title">Get warned before harm, not after</h3>
            <p class="wt-how__body">Continuous monitoring alerts you the moment a new risky approval appears — straight to your inbox. You're warned in time to act.</p>
            <span class="wt-how__tag">Free · coming soon</span>
        </article>

    </div>
</section>
