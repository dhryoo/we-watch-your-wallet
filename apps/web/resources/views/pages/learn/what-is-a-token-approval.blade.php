<x-scan-layout title="What Is a Token Approval? Allowances Explained in Plain English" description="Learn what a token approval is, why dApps need one to move your ERC-20 tokens or NFTs, and why approvals stay active until you revoke them yourself.">
    <div class="wt-doc">
        <h1>What Is a Token Approval? Allowances Explained in Plain English</h1>
        <p class="wt-doc__lead">A from-zero guide to token approvals: why dApps request them, how allowances and setApprovalForAll work, and why approvals persist until you revoke them.</p>

        <h2>What a token approval actually is</h2>
        <p>A token approval is permission you give a smart contract to move certain tokens out of your wallet on your behalf. When you approve, you set an allowance: a number recorded on the blockchain that says how much of a specific token a specific contract may spend. That is why you will also hear the term token allowance. The two phrases describe the same thing.</p>
        <p>Approvals are a normal part of using decentralized apps, or dApps. If you have ever swapped a token on a decentralized exchange, you have almost certainly granted at least one, perhaps without noticing what the confirmation was for.</p>

        <h2>Why dApps need approvals to move your tokens</h2>
        <p>Your ERC-20 tokens do not really sit inside your wallet app. Each token is its own smart contract, and that contract keeps a ledger of who owns how much. Your wallet holds the key that controls your entry in that ledger, nothing more.</p>
        <p>A dApp such as a decentralized exchange cannot reach into that ledger on its own. The token standard requires your permission first. So the dApp asks you to call the token's approve function, which records an allowance for the dApp's contract. That is why your first trade with a new token often needs two confirmations: one transaction to approve, then one to swap.</p>

        <h2>setApprovalForAll: the NFT version</h2>
        <p>NFTs use a related but broader permission called setApprovalForAll. Instead of approving a specific amount, it is all or nothing: you allow a contract, usually a marketplace, to transfer every NFT you own in that collection, including ones you receive later.</p>
        <p>Legitimate marketplaces rely on this because it is convenient; you approve a collection once and can then list any item from it. It is also a common request from fake mint sites and drainer kits, because a single confirmation hands over access to an entire collection.</p>

        <h2>Approvals persist until revoked, and they come in two sizes</h2>
        <p>Here is the part that surprises most people: an approval does not end when your trade does. Whatever allowance is left over stays active until you revoke it. If you approved more than the dApp actually spent, you can stop using it for years and the leftover permission will still be live. If that contract is later exploited, or was malicious from the start, whoever controls it can move your approved tokens, up to that remaining allowance, without any new signature from you.</p>
        <p>It also matters how large the approval is:</p>

        <h2>Permit and Permit2: approvals by signature</h2>
        <p>Some tokens support an alternative called Permit. Instead of sending an approval transaction yourself, you sign a message and the dApp submits it to the blockchain for you, so you do not pay gas for the approval step. Permit2, a contract from the Uniswap team, extends the idea to more tokens: you give the Permit2 contract a normal on-chain approval once per token, then grant individual dApps allowances through signatures. Those allowances carry expiry dates, though apps often set them far in the future. Signatures feel lighter than transactions, but they can grant the same real spending power, so they deserve the same care before you sign.</p>

        <h2>How to see what you have approved</h2>
        <p>Approvals are public on-chain data tied to your address, so you can review them without connecting a wallet or signing anything. A read-only approval scanner like ours takes just an address or ENS name and lists your active approvals across Ethereum, Base, Arbitrum, Polygon, and BNB Chain, flagging the ones that look risky, such as unlimited allowances granted to unverified contracts.</p>
        <p>If you find an approval you no longer want, you can cancel it with a revoke transaction sent from your own wallet. A tool like revoke.cash walks you through this; the transaction always happens in your wallet, under your control, and it costs a small amount of gas per approval. Reviewing old approvals every so often is a simple habit, and looking is always free.</p>

        <h2>Frequently asked questions</h2>
        <p><strong>Is a token approval the same as connecting my wallet to a site?</strong><br>No. Connecting a wallet only shares your address with a site, while an approval is an on-chain permission that lets a contract move specific tokens. Disconnecting from a site does not remove any approvals you granted.</p>
        <p><strong>Do token approvals expire on their own?</strong><br>Standard ERC-20 approvals do not expire; they stay active until the allowance is used up or you revoke it. Some signature-based systems, such as Permit2, attach expiry dates, though apps often set them far in the future.</p>
        <p><strong>Does revoking an approval cost anything?</strong><br>Yes. Revoking is a normal transaction sent from your own wallet, so it costs a small gas fee for each approval you cancel. Checking what you have approved is free.</p>
        <p><strong>Can one approval drain my whole wallet?</strong><br>No single approval covers everything you own. An ERC-20 approval applies to one token for one contract, setApprovalForAll applies to one NFT collection, and no approval can ever move your chain's native coin, such as ETH. But an unlimited allowance does cover your entire balance of that one token, and old approvals add up, which is why reviewing them matters.</p>

        <h2>Keep learning</h2>
        <ul>
            <li><a href="{{ url('/learn/how-to-revoke-token-approvals') }}">How to Revoke Token Approvals (Step by Step)</a></li>
            <li><a href="{{ url('/learn/unlimited-token-approvals') }}">Unlimited Token Approvals: What the Risk Actually Is</a></li>
            <li><a href="{{ url('/learn/approval-phishing') }}">Approval Phishing: How Wallet Drainers Actually Work</a></li>
            <li><a href="{{ url('/how-it-works') }}">How We Watch Your Wallet works</a></li>
        </ul>

        <a href="{{ url('/') }}" class="wt-btn wt-btn--solid wt-doc__back">Scan a wallet</a>
    </div>
</x-scan-layout>
