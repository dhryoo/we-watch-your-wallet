<x-scan-layout title="Unlimited Token Approvals: What the Risk Actually Is" description="Unlimited token approvals let a contract move your entire balance of a token at any time. Learn the real risk, the capped alternative, and how to check yours.">
    <div class="wt-doc">
        <h1>Unlimited Token Approvals: What the Risk Actually Is</h1>
        <p class="wt-doc__lead">Why dApps ask for unlimited token approvals, how a malicious or later-exploited contract can move your whole balance of a token, and how to find and revoke yours.</p>

        <h2>What an unlimited token approval is</h2>
        <p>When you use a decentralized app, it usually cannot move your tokens by itself. You first grant an approval: permission for a specific smart contract to spend a specific token from your wallet. The approval includes an amount. It can be exactly what you need for one trade, or it can be effectively unlimited.</p>
        <p>An unlimited approval sets the allowance to a huge number, often the maximum value the token contract accepts. In practice it means: this contract may move any amount of this token from my wallet, at any time, without asking me again.</p>
        <p>Usually you grant an approval by sending a transaction. Some tokens also accept a signed message, called a permit, that grants the same allowance without you sending a separate transaction. NFT collections have their own version, setApprovalForAll, which covers every item in the collection by design. The mechanics differ, but the result is the same kind of standing permission.</p>

        <h2>Why dApps ask for unlimited amounts</h2>
        <p>It is mostly a user experience choice, not a trick. A standard approval is its own on-chain transaction, and every transaction costs gas. If a dApp requested an exact-amount approval each time, you would pay for two transactions on every trade: one to approve, one to act. With a single unlimited approval, you approve once and then just use the app.</p>
        <p>For an exchange or lending protocol you use often, this saves real money and friction. Most dApps that request unlimited approvals are not trying to steal from you. But the convenience comes with a cost that is easy to overlook.</p>

        <h2>The actual risk</h2>
        <p>An approval is a standing permission recorded on the blockchain. A standard approval does not expire on its own, and it does not care whether you still use the dApp. Some newer approval systems, such as Permit2, add expiry times, but the classic token approval has none. If the contract you approved is malicious, or is later exploited, it can move your entire balance of that token in a single transaction. Three details are easy to miss.</p>
        <ul>
            <li>It covers your future balance too. If you approved a stablecoin last year and top up your wallet today, the new tokens fall under the old approval.</li>
            <li>It needs no further sign-off from you. Once the approval exists, the contract can move tokens without another signature or confirmation.</li>
            <li>It survives being forgotten. Approvals granted to dApps you tried once and never revisited can stay active for years.</li>
        </ul>

        <h2>When legitimate protocols get exploited</h2>
        <p>You do not need to touch a scam to be exposed. There have been cases where a real, widely used protocol was exploited, and the attacker abused the protocol's own approved contracts to pull tokens from wallets that had granted unlimited allowances, sometimes long after those users had stopped using the protocol.</p>
        <p>Those users did nothing wrong at the time. They approved a genuine dApp that worked as promised. The risk arrived later, when a bug was found. That is the core problem with leaving unlimited approvals open indefinitely: you are trusting not just the contract as it behaves today, but everything that could happen to it in the future.</p>

        <h2>Capped approvals and their tradeoff</h2>
        <p>The alternative is a capped approval: you approve only the amount needed for the current action. Many wallets let you edit the requested amount before signing. If the contract is later compromised, an attacker can take at most the leftover allowance, which is often zero.</p>
        <p>The tradeoff is cost and friction. Each new action may need a fresh approval, which means another transaction and more gas. On Ethereum mainnet during busy periods, that adds up. On lower-fee chains like Base, Arbitrum, or Polygon, the extra cost is usually small. There is no single right answer; it is a tradeoff between convenience and how much of a token you are willing to expose.</p>

        <h2>How to find your unlimited approvals</h2>
        <p>Approvals are public data, so you can check them without connecting a wallet or signing anything. A read-only approval scanner like ours only needs your wallet address: paste it and you can see which contracts can spend your tokens, on which chains, and which allowances are unlimited.</p>
        <p>Pay particular attention to unlimited approvals on tokens you hold in meaningful amounts, approvals granted to dApps you no longer use, and spender contracts you do not recognize. If you decide to revoke one, the transaction happens in your own wallet, typically through a tool like revoke.cash. Revoking sets the allowance back to zero and costs a small amount of gas. At no point does the process require sharing your keys, and no scanner can revoke on your behalf.</p>

        <h2>Frequently asked questions</h2>
        <p><strong>Does an unlimited approval mean a dApp already has my tokens?</strong><br>No. Your tokens stay in your wallet. The approval is a permission that lets one specific contract move one specific token if it chooses to, which is exactly why a compromised contract is dangerous.</p>
        <p><strong>Should I always refuse unlimited approvals?</strong><br>There is no single right answer. Unlimited approvals save gas on dApps you use regularly, while capped approvals limit how much a compromised contract could take. Weigh the convenience against the size of the balance you would be exposing.</p>
        <p><strong>Do token approvals expire on their own?</strong><br>A standard approval does not. It stays active until you revoke it or the approved amount is spent down, so approvals from years ago can still be live today. Some newer systems, such as Permit2, add an expiry time, but do not assume an old approval has lapsed.</p>
        <p><strong>Does revoking an approval cost anything?</strong><br>Yes. Revoking is an on-chain transaction, so it costs a small amount of gas. It is usually cheap on chains like Base or Polygon and more expensive on Ethereum mainnet when the network is busy.</p>

        <h2>Keep learning</h2>
        <ul>
            <li><a href="{{ url('/learn/what-is-a-token-approval') }}">What Is a Token Approval? Allowances Explained in Plain English</a></li>
            <li><a href="{{ url('/learn/how-to-revoke-token-approvals') }}">How to Revoke Token Approvals (Step by Step)</a></li>
            <li><a href="{{ url('/learn/approval-phishing') }}">Approval Phishing: How Wallet Drainers Actually Work</a></li>
            <li><a href="{{ url('/how-it-works') }}">How We Watch Your Wallet works</a></li>
        </ul>

        <a href="{{ url('/') }}" class="wt-btn wt-btn--solid wt-doc__back">Scan a wallet</a>
    </div>
</x-scan-layout>
