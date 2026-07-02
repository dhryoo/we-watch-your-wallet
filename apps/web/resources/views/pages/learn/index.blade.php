<x-scan-layout title="Learn" description="Plain-English guides to token approvals: what they are, why unlimited approvals are risky, how approval phishing works, and how to revoke — free and read-only.">
    <div class="wt-doc">
        <h1>Learn</h1>
        <p class="wt-doc__lead">Plain-English guides to token approvals and wallet safety. No hype — just how it works, so you can review and revoke your own approvals with confidence.</p>

        <h2><a href="{{ url('/learn/what-is-a-token-approval') }}">What Is a Token Approval? Allowances Explained in Plain English</a></h2>
        <p>A from-zero guide to token approvals: why dApps request them, how allowances and setApprovalForAll work, and why approvals persist until you revoke them.</p>

        <h2><a href="{{ url('/learn/how-to-revoke-token-approvals') }}">How to Revoke Token Approvals (Step by Step)</a></h2>
        <p>A plain-English walkthrough of revoking token approvals: what the transaction actually does, how to do it on revoke.cash from your own wallet, and what revoking cannot fix.</p>

        <h2><a href="{{ url('/learn/unlimited-token-approvals') }}">Unlimited Token Approvals: What the Risk Actually Is</a></h2>
        <p>Why dApps ask for unlimited token approvals, how a malicious or later-exploited contract can move your whole balance of a token, and how to find and revoke yours.</p>

        <h2><a href="{{ url('/learn/approval-phishing') }}">Approval Phishing: How Wallet Drainers Actually Work</a></h2>
        <p>How wallet drainers use approval phishing to steal tokens without your seed phrase — the lures, the signatures to watch for, and how to clean up afterward.</p>

        <a href="{{ url('/') }}" class="wt-btn wt-btn--solid wt-doc__back">Scan a wallet</a>
    </div>
</x-scan-layout>
