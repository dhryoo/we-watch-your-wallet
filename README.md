# We Watch Your Wallet

A **free, non-custodial, read-only** scanner for risky Ethereum token approvals.
Live at **[wewatchyourwallet.com](https://wewatchyourwallet.com)**.

Paste a wallet address → see which token approvals could drain it (unlimited
allowances, unverified or malicious spenders) → revoke the dangerous ones
yourself in your own wallet. We never touch your keys or funds.

## Why you can trust it (verify in the code)

- **No keys, ever** — there is no private-key, seed-phrase, or wallet-connect
  flow anywhere in the codebase. We only read the **public** address you paste.
- **Read-only** — the scan path (`app/Scan/GoPlusScanner.php`) only *reads*
  public on-chain approval data (via GoPlus). We never create, sign, or send a
  transaction; revoking links out to [revoke.cash](https://revoke.cash), which
  you execute yourself.
- **Honest** — if data can't be fetched we mark the result `STALE`, never "safe".
- **No tracking cookies, no ads.** (Optional cookieless Cloudflare Web Analytics.)
- **Non-advisory** — explanations never give buy/sell/trading advice.

See the in-app pages **/how-it-works** and **/non-custodial** for the plain-English version.

## Stack

- PHP 8.3+ / Laravel (`apps/web`), SQLite — sessions, cache, and rate limits all
  in one file; idle cost ≈ zero.
- Risk data: [GoPlus](https://gopluslabs.io) (keyless public API).
- Optional, off by default (gated by env): Claude (Haiku) plain-language risk
  explanations, Cloudflare Turnstile bot-gate, Cloudflare Web Analytics.
- Risk scoring source of truth: the TypeScript `packages/detect-engine`
  (re-implemented in PHP for the request-driven free scan).

## Run locally

```bash
cd apps/web
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve        # http://127.0.0.1:8000
php artisan test         # full suite
```

All third-party integrations are optional — with no API keys set, the app runs
fully (template explanations, captcha bypassed in dev, no analytics).

## Deploy

Standard Laravel: build on the server, run migrations, and serve
`apps/web/public` behind nginx + PHP-FPM with TLS. All secrets live only in the
server `.env` — never in this repo.

## License

[MIT](LICENSE). Informational only — not financial, investment, or legal advice.
