<x-scan-layout title="Free wallet risk scan">
    <section class="wt-hero">
        <div class="wt-hero__main">
            <span class="wt-chain-chip" style="margin-bottom:20px;"><span class="wt-chain-chip__dot"></span><span class="wt-chain-chip__label">Ethereum · read-only</span></span>
            <h1 style="margin:0 0 14px;font-family:var(--font-display);font-weight:300;font-size:46px;letter-spacing:-.06em;color:var(--wt-green);max-width:720px;">Scan a wallet for risky token approvals</h1>
            <p style="margin:0 0 28px;font-size:16px;line-height:1.55;letter-spacing:-.02em;color:#50624a;max-width:560px;">Enter a 0x address or ENS name to see unlimited, unverified, or malicious approvals — free and non-custodial. We never touch your keys or funds, and you revoke yourself in your own wallet.</p>

            <form method="post" action="/scan" data-scan-loading style="max-width:560px;">
                @csrf
                <div class="wt-cta__inputrow">
                    <select name="chain" class="wt-chain-select" aria-label="Network">
                        @foreach ($chains as $c)
                            <option value="{{ $c['slug'] }}" @selected(old('chain', 'ethereum') === $c['slug'])>{{ $c['label'] }}</option>
                        @endforeach
                    </select>
                    <input id="wt-address-input" class="wt-input" type="text" name="address" value="{{ old('address') }}" placeholder="0x… address or vitalik.eth" aria-label="Wallet address or ENS name" autocomplete="off" spellcheck="false">
                    <button class="wt-btn wt-btn--solid" type="submit">Scan</button>
                </div>
                <button type="button" id="wt-qr-open" class="wt-qr-trigger" hidden>
                    <svg class="wt-qr-trigger__icon" width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                        <rect x="1.2" y="1.2" width="5" height="5" rx="1.2" stroke="currentColor" stroke-width="1.4" />
                        <rect x="9.8" y="1.2" width="5" height="5" rx="1.2" stroke="currentColor" stroke-width="1.4" />
                        <rect x="1.2" y="9.8" width="5" height="5" rx="1.2" stroke="currentColor" stroke-width="1.4" />
                        <rect x="9.8" y="9.8" width="2.1" height="2.1" fill="currentColor" />
                        <rect x="12.7" y="12.7" width="2.1" height="2.1" fill="currentColor" />
                    </svg>
                    Scan a wallet QR code
                </button>
                @error('address')
                    <div style="margin-top:12px;font-family:var(--font-mono),monospace;font-size:12px;letter-spacing:.02em;color:#b3382e;">{{ $message }}</div>
                @enderror
                {{-- client-side validation message (shown without a page reload, so Turnstile stays solved) --}}
                <div id="wt-address-error" role="alert" hidden style="margin-top:12px;font-family:var(--font-mono),monospace;font-size:12px;letter-spacing:.02em;color:#b3382e;"></div>

                @if ($sitekey)
                    <div class="wt-home-verify">
                        <div class="cf-turnstile" data-sitekey="{{ $sitekey }}"></div>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                    </div>
                @endif

                <p style="margin:16px 0 0;font-family:var(--font-mono),monospace;font-size:10.5px;line-height:1.6;letter-spacing:.01em;color:#9aa897;">We analyze public on-chain data read-only. Informational only — not financial, investment, or legal advice.</p>
            </form>
        </div>

        {{-- Hero illustration: a stylized preview of an actual scan result (example data, not live). --}}
        <div class="wt-hero__art" role="img" aria-label="Example scan result preview: risk score 70 of 100, one urgent unlimited approval flagged to revoke. Illustration only.">
            <svg viewBox="0 0 440 412" fill="none" xmlns="http://www.w3.org/2000/svg" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <defs>
                    <filter id="wtHeroShadow" x="-20%" y="-20%" width="140%" height="140%">
                        <feDropShadow dx="0" dy="10" stdDeviation="14" flood-color="#2E4F21" flood-opacity="0.10"/>
                    </filter>
                </defs>

                {{-- depth: faint mint orbs --}}
                <circle cx="392" cy="74" r="92" fill="#ECFCF2"/>
                <circle cx="420" cy="40" r="52" fill="#A0F1BD" opacity="0.30"/>

                {{-- watchtower motif watching the card, top-left --}}
                <g stroke="#2E4F21" stroke-width="2.4" transform="translate(40,12)">
                    <path d="M4 30 L20 16 L36 30"/>
                    <rect x="9" y="30" width="22" height="13" rx="2" fill="#fff"/>
                    <circle cx="20" cy="36.5" r="3" fill="#A0F1BD"/>
                    <path d="M12 43 L7 64 M28 43 L33 64 M16 43 L14 64 M24 43 L26 64 M9 53 H31"/>
                    <circle cx="20" cy="13" r="2.6" fill="#A0F1BD" stroke="none"/>
                </g>
                <path d="M58 72 L150 96 M58 80 L120 150" stroke="#A0F1BD" stroke-width="2" stroke-dasharray="2 5" opacity="0.8"/>

                {{-- result card --}}
                <g filter="url(#wtHeroShadow)">
                    <rect x="46" y="78" width="348" height="318" rx="22" fill="#fff" stroke="#E3E8E3" stroke-width="1.5"/>
                </g>

                {{-- header: wallet chip + EXAMPLE marker --}}
                <rect x="66" y="100" width="196" height="28" rx="14" fill="#ECFCF2" stroke="#CFE9D9" stroke-width="1.2"/>
                <circle cx="82" cy="114" r="3.6" fill="#2E4F21"/>
                <text x="94" y="118" font-family="'Roboto Mono',monospace" font-size="11" letter-spacing="0.2" fill="#3C4A36">Ethereum · 0x7a3f…9c2D</text>
                <text x="374" y="118" text-anchor="end" font-family="'Roboto Mono',monospace" font-size="9" letter-spacing="1.2" fill="#9AA897">EXAMPLE</text>

                <line x1="66" y1="146" x2="374" y2="146" stroke="#EEF1EE" stroke-width="1"/>

                {{-- risk score ring --}}
                <circle cx="116" cy="200" r="38" stroke="#EDF1ED" stroke-width="11" fill="none"/>
                <circle cx="116" cy="200" r="38" stroke="#B3382E" stroke-width="11" fill="none" stroke-linecap="round" stroke-dasharray="167 72" transform="rotate(-90 116 200)"/>
                <text x="116" y="206" text-anchor="middle" font-family="'Work Sans',sans-serif" font-weight="400" font-size="30" letter-spacing="-1" fill="#2E4F21">70</text>
                <text x="116" y="221" text-anchor="middle" font-family="'Roboto Mono',monospace" font-size="9" fill="#9AA897">/ 100</text>
                <text x="116" y="262" text-anchor="middle" font-family="'Roboto Mono',monospace" font-size="9" letter-spacing="0.8" fill="#7D9276">RISK SCORE</text>

                {{-- right of ring: URGENT chip + count --}}
                <rect x="184" y="168" width="96" height="27" rx="13.5" fill="#FBEAE8" stroke="#EFB5B0" stroke-width="1.2"/>
                <path d="M200 187 l5 -11 l5 11 z" fill="#B3382E"/>
                <text x="214" y="186" font-family="'Work Sans',sans-serif" font-weight="600" font-size="12" letter-spacing="0.3" fill="#8E2018">URGENT</text>
                <text x="184" y="228" font-family="'Work Sans',sans-serif" font-weight="300" font-size="40" letter-spacing="-2" fill="#2E4F21">1</text>
                <text x="212" y="219" font-family="'Work Sans',sans-serif" font-weight="400" font-size="13" fill="#50624A">risky approval</text>
                <text x="212" y="236" font-family="'Work Sans',sans-serif" font-weight="400" font-size="13" fill="#50624A">found</text>

                {{-- approval rows: 2 safe + 1 flagged --}}
                <rect x="66" y="276" width="308" height="34" rx="9" fill="#F7F9F7" stroke="#ECEFEC" stroke-width="1"/>
                <text x="80" y="297" font-family="'Work Sans',sans-serif" font-weight="600" font-size="12.5" fill="#2E4F21">USDC</text>
                <text x="124" y="297" font-family="'Roboto Mono',monospace" font-size="10" fill="#7D9276">Uniswap · verified</text>
                <circle cx="356" cy="293" r="9" fill="#ECFCF2" stroke="#2E4F21" stroke-width="1.6"/>
                <path d="M352 293 l3 3 l5 -6" stroke="#2E4F21" stroke-width="1.8"/>

                <rect x="66" y="316" width="308" height="34" rx="9" fill="#F7F9F7" stroke="#ECEFEC" stroke-width="1"/>
                <text x="80" y="337" font-family="'Work Sans',sans-serif" font-weight="600" font-size="12.5" fill="#2E4F21">WETH</text>
                <text x="126" y="337" font-family="'Roboto Mono',monospace" font-size="10" fill="#7D9276">1inch · capped</text>
                <circle cx="356" cy="333" r="9" fill="#ECFCF2" stroke="#2E4F21" stroke-width="1.6"/>
                <path d="M352 333 l3 3 l5 -6" stroke="#2E4F21" stroke-width="1.8"/>

                <rect x="66" y="356" width="308" height="34" rx="9" fill="#FBEAE8" stroke="#EFB5B0" stroke-width="1.2"/>
                <rect x="66" y="356" width="4" height="34" rx="2" fill="#B3382E"/>
                <text x="80" y="377" font-family="'Work Sans',sans-serif" font-weight="600" font-size="12.5" fill="#2E4F21">USDT</text>
                <text x="124" y="377" font-family="'Roboto Mono',monospace" font-size="10" fill="#A9520F">Unknown · unlimited</text>
                <rect x="312" y="365" width="54" height="18" rx="9" fill="#2E4F21"/>
                <text x="339" y="377.5" text-anchor="middle" font-family="'DM Sans',sans-serif" font-weight="500" font-size="10" fill="#A0F1BD">Revoke</text>
            </svg>
        </div>

        {{-- QR scanner overlay (progressive enhancement; position:fixed, outside the hero grid flow) --}}
        <div id="wt-qr-overlay" class="wt-qr-overlay" hidden>
            <div class="wt-qr-modal">
                <div class="wt-qr-modal__head">
                    <span class="wt-qr-modal__title">Scan a wallet QR</span>
                    <button type="button" id="wt-qr-cancel" class="wt-qr-close" aria-label="Close">✕</button>
                </div>
                <div class="wt-qr-stage">
                    <video id="wt-qr-video" class="wt-qr-video" muted playsinline></video>
                    <div class="wt-qr-reticle" aria-hidden="true"></div>
                </div>
                <canvas id="wt-qr-canvas" hidden></canvas>
                <p id="wt-qr-msg" class="wt-qr-msg" role="status" aria-live="polite" data-state="idle">
                    <span class="wt-qr-msg__icon" aria-hidden="true"><span></span></span>
                    <span class="wt-qr-msg__body">
                        <span class="wt-qr-msg__lead"></span>
                        <span class="wt-qr-msg__text"></span>
                    </span>
                    <span class="wt-qr-msg__count" hidden></span>
                    <span class="wt-qr-msg__sr wt-visually-hidden"></span>
                </p>
                <div class="wt-qr-foot">
                    <label class="wt-qr-file-btn">Upload a photo<input type="file" id="wt-qr-file" accept="image/*" capture="environment" hidden></label>
                    <span class="wt-qr-note">Scanning runs on your device — the camera image never leaves your browser.</span>
                </div>
            </div>
        </div>
    </section>

    <x-how-protects />

    <script src="{{ asset('js/jsQR.js') }}"></script>
    <script src="{{ asset('js/wallet-qr.js') }}"></script>
    {{-- loads before scan-loading.js (in the layout) so its submit guard runs first --}}
    <script src="{{ asset('js/scan-validate.js') }}"></script>
</x-scan-layout>
