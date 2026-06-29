<x-scan-layout title="How it works" description="How We Watch Your Wallet scans your Ethereum token approvals, scores risk, and helps you revoke dangerous approvals yourself — read-only and non-custodial, we never touch your keys or funds.">
    <div class="wt-doc">
        <h1>How it works</h1>
        <p class="wt-doc__lead">We Watch Your Wallet is a free, read-only scanner for risky Ethereum token approvals. Paste an address, see what could drain it, and revoke the dangerous ones yourself — we never touch your keys or funds.</p>

        <h2>1. You paste a wallet address</h2>
        <p>Just the public address. No login, no wallet connection, no signing. We only ever read public on-chain data — the same data anyone can see on a block explorer.</p>

        <h2>2. We read and score the approvals</h2>
        <p>We list the contracts you've granted permission to move your tokens, and score each by risk — unlimited allowances, unverified or malicious spenders, and more. The data comes from public on-chain sources (via GoPlus). Results are informational only, never financial or trading advice.</p>

        <h2>3. You revoke the risky ones yourself</h2>
        <p>For anything dangerous, we link you to revoke.cash, where you cancel the approval from your own wallet. We never create, sign, or send a transaction for you.</p>

        <h2>If we can't fetch the data</h2>
        <p>If on-chain data can't be fetched, we mark the result STALE rather than pretend a wallet is safe. We'd rather say "we don't know" than be wrong about your security.</p>

        <a href="{{ url('/') }}" class="wt-btn wt-btn--solid wt-doc__back">Scan a wallet</a>
    </div>
</x-scan-layout>
