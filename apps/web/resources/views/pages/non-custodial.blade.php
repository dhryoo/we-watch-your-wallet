<x-scan-layout title="What 'non-custodial' means" description="We Watch Your Wallet is non-custodial and read-only: we never access your private keys or funds, never ask you to connect a wallet, and only read public on-chain data. You revoke approvals yourself.">
    <div class="wt-doc">
        <h1>Non-custodial, read-only</h1>
        <p class="wt-doc__lead">A security tool should be the one you trust most. So here is exactly what we do — and never do — with your wallet.</p>

        <h2>What we never do</h2>
        <ul>
            <li>We never ask for your private key, seed phrase, or password.</li>
            <li>We never ask you to connect a wallet, sign a message, or approve a transaction.</li>
            <li>We never move, hold, or have access to your funds.</li>
            <li>We never create, sign, or send transactions — revoking is done by you, in your own wallet.</li>
            <li>We don't use tracking cookies and we don't run ads.</li>
        </ul>

        <h2>What we actually do</h2>
        <ul>
            <li>Read public on-chain data for the address you paste (the same data anyone can see on a block explorer).</li>
            <li>Score the token approvals on that address and explain the risks in plain language.</li>
            <li>Link you to revoke.cash so you can cancel risky approvals yourself.</li>
        </ul>

        <h2>Why pasting an address is safe</h2>
        <p>A wallet address is public — sharing it cannot move your funds or grant anyone access. Only your private key can authorize a transaction, and we never see it. Moving funds always requires a signature from your own wallet, which only you control.</p>

        @if ($repo = config('scan.repo_url'))
            <h2>Verify it yourself</h2>
            <p>You don't have to take our word for it — the code is open source. Read it and confirm it only reads public data and never touches keys or funds: <a href="{{ $repo }}" target="_blank" rel="noopener noreferrer">source code</a>.</p>
        @endif

        <a href="{{ url('/') }}" class="wt-btn wt-btn--solid wt-doc__back">Scan a wallet</a>
    </div>
</x-scan-layout>
