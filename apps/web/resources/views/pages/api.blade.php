<x-scan-layout title="API">
    <div class="wt-doc">
        <h1>Public API</h1>
        <p class="wt-doc__lead">A free, read-only JSON endpoint to scan any Ethereum address for risky token &amp; NFT approvals. No key, no auth, CORS-enabled — embed it anywhere.</p>

        <h2>Endpoint</h2>
        <p><code>GET https://wewatchyourwallet.com/api/scan/{address}</code></p>
        <p>Example:</p>
        <pre>curl https://wewatchyourwallet.com/api/scan/0xd8dA6BF26964aF9D7eEd9e03E53415D37aA96045</pre>

        <h2>Response</h2>
        @verbatim
        <pre>{
  "address": "0x…",
  "chainId": 1,
  "scannedAt": "2026-06-28T07:00:00Z",
  "score": 80,
  "severity": "URGENT",
  "approvalsReviewed": 42,
  "stale": false,
  "failed": false,
  "risks": [
    {
      "token": "USDC",
      "tokenAddress": "0xA0b8…48Ce",
      "spender": "MALICIOUS",
      "spenderAddress": "0x1111…1111",
      "severity": "URGENT",
      "unlimited": true,
      "limit": null,
      "explanation": "A spender flagged as malicious holds an unlimited approval on your USDC…",
      "revokeUrl": "https://revoke.cash/address/0x…?chainId=1"
    }
  ],
  "disclaimer": "Informational only — not financial, investment, or legal advice. Read-only and non-custodial."
}</pre>
        @endverbatim

        <h2>Severity</h2>
        <p>Each risk and the overall result is one of <code>INFO</code> · <code>WATCH</code> · <code>WARN</code> · <code>URGENT</code>.</p>

        <h2>Limits &amp; honesty</h2>
        <ul>
            <li>Rate-limited per IP per day (shared with the website scan). Over the limit returns <code>429</code>.</li>
            <li>If upstream risk data is unavailable, the API returns <code>503</code> — never a fake "clean" result.</li>
            <li>Partial upstream data sets <code>"stale": true</code> (the picture may be incomplete).</li>
            <li>CORS is open (<code>Access-Control-Allow-Origin: *</code>); the endpoint is sessionless and sets no cookies.</li>
            <li>Read-only &amp; non-custodial: we only read public on-chain data, and revoking is done by you in your own wallet.</li>
            <li>Informational only — not financial, investment, or legal advice.</li>
        </ul>

        <a href="{{ url('/') }}" class="wt-btn wt-btn--solid wt-doc__back">Scan a wallet</a>
    </div>
</x-scan-layout>
