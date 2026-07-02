<x-scan-layout title="How to Revoke Token Approvals (Step by Step)" description="Learn how to revoke token approvals from your own wallet, step by step. What revoking costs, which approvals to prioritize, and what it can&#x27;t undo.">
    <div class="wt-doc">
        <h1>How to Revoke Token Approvals (Step by Step)</h1>
        <p class="wt-doc__lead">A plain-English walkthrough of revoking token approvals: what the transaction actually does, how to do it on revoke.cash from your own wallet, and what revoking cannot fix.</p>

        <h2>What revoking a token approval actually does</h2>
        <p>When you use a decentralized app, you often grant a token approval first. That approval gives another address, usually a smart contract, called a spender, permission to move a specific token from your wallet. The approval stays active until you change it or it is used up, even if you stop using the app the same day.</p>
        <p>Revoking is how you change it. A revoke is a normal on-chain transaction sent from your own wallet. For a regular token, it sets the spender's allowance back to zero. For NFTs, it clears the approval that let a contract manage a single item or an entire collection. Either way, once the transaction confirms, that spender can no longer move those assets through that approval.</p>
        <p>Because a revoke is a real transaction, it costs a small amount of gas, paid in the chain's native token. Fees vary with network conditions, and they are generally lower on chains like Base, Arbitrum, Polygon, and BNB Chain than on Ethereum mainnet.</p>

        <h2>Step 1: see what you have approved</h2>
        <p>Before revoking anything, get the full picture. Wallets rarely show approvals in one place, and most people have more of them than they expect.</p>
        <p>You can check without connecting anything. A read-only approval scanner like ours lets you paste a wallet address, or an ENS name, and see active approvals across chains, with risky ones flagged. A scan reads public blockchain data only. It asks for no signatures and changes nothing on-chain.</p>
        <p>From the results, make a short list of approvals you want to remove. For each one, note the token, the spender contract, and the chain it lives on.</p>

        <h2>Step 2: revoke from your own wallet on revoke.cash</h2>
        <p>The revoke itself happens in your wallet, because only your wallet can sign the transaction. revoke.cash is a widely used, free tool for this.</p>
        <p>Repeat for each item on your list. If your approvals sit on several chains, you will switch networks and send a separate transaction on each one.</p>
        <ul>
            <li>Open revoke.cash and connect the wallet that holds the approvals.</li>
            <li>Select the network where the approval was granted.</li>
            <li>Find the approval in the list, using the token and spender details from your notes.</li>
            <li>Click revoke and read the transaction your wallet shows you. A revoke should set an allowance to zero or clear an operator, not grant anything new.</li>
            <li>Confirm, pay the gas fee, and wait for the transaction to complete.</li>
            <li>Check that the approval now shows as removed.</li>
        </ul>

        <h2>It works the same on every supported chain</h2>
        <p>The process above is identical on Ethereum, Base, Arbitrum, Polygon, and BNB Chain. These networks use the same token standards, so allowances and operator approvals behave the same way everywhere.</p>
        <p>Two things do differ. Gas is paid in each chain's own native token, and fee levels vary. Also, approvals are per chain: revoking a spender on Ethereum does nothing to an approval you granted the same app on Base. Each chain needs its own revoke.</p>

        <h2>Which approvals to revoke first</h2>
        <p>You do not have to clear everything at once. Since each revoke costs gas, start where the risk is highest.</p>
        <p>Approvals to well-known contracts you actively use are lower priority. You can still revoke them if you prefer; the trade-off is that you will need to approve again next time you use the app.</p>
        <ul>
            <li>Spenders flagged as malicious. If a scanner or community report links a spender to drainer activity, revoke it first.</li>
            <li>Unverified or unidentifiable contracts. If you cannot tell what a spender is or why it has access, that is a reasonable one to remove.</li>
            <li>Unlimited allowances. These let a spender move your entire balance of a token, now and in the future.</li>
            <li>Approvals for apps you no longer use. An idle approval carries risk while giving you nothing back.</li>
        </ul>

        <h2>What revoking does not do</h2>
        <p>Revoking removes a permission going forward. It is worth being clear about what it cannot do.</p>
        <p>It does not recover stolen funds. If a spender already moved tokens out of your wallet, those transfers are final. Revoking only prevents future transfers through that same approval.</p>
        <p>It does not fix a compromised seed phrase or private key. Someone who holds your key controls the wallet directly and does not need approvals at all, so revoking cannot make that wallet trustworthy again.</p>
        <p>It does not cancel a permission you signed but that has not been used yet. Some apps skip the approval transaction and ask for an off-chain signature instead, often called a permit. Until that signature is used on-chain, it will not appear in any approval list, so a clean scan cannot rule one out. Permissions that route through the Permit2 system do depend on a standing token approval to the Permit2 contract, and revoking that approval closes off that route.</p>
        <p>It only covers what you revoke. Each token-and-spender pair is a separate approval, so removing one leaves the rest untouched. That is why a periodic review of your full approval list is more useful than a single one-off cleanup.</p>

        <h2>Frequently asked questions</h2>
        <p><strong>Does revoking a token approval cost money?</strong><br>Yes. A revoke is an on-chain transaction, so you pay a small gas fee in the chain's native token. Fees are usually higher on Ethereum mainnet and lower on chains like Base, Arbitrum, Polygon, and BNB Chain.</p>
        <p><strong>Will revoking an approval remove my tokens or change my balance?</strong><br>No. Revoking only removes a spender's permission to move your tokens. Your balances stay exactly where they are.</p>
        <p><strong>Do I need to revoke approvals for apps I still use?</strong><br>Not necessarily. If you know the app and still use it, you can leave the approval in place. Some people still revoke unlimited allowances and approve a smaller amount the next time they use the app.</p>
        <p><strong>Can I revoke an approval without connecting my wallet?</strong><br>No. Checking approvals can be done read-only with just an address, but revoking requires a signed transaction, and only your own wallet can sign it.</p>

        <h2>Keep learning</h2>
        <ul>
            <li><a href="{{ url('/learn/what-is-a-token-approval') }}">What Is a Token Approval? Allowances Explained in Plain English</a></li>
            <li><a href="{{ url('/learn/unlimited-token-approvals') }}">Unlimited Token Approvals: What the Risk Actually Is</a></li>
            <li><a href="{{ url('/learn/approval-phishing') }}">Approval Phishing: How Wallet Drainers Actually Work</a></li>
            <li><a href="{{ url('/how-it-works') }}">How We Watch Your Wallet works</a></li>
        </ul>

        <a href="{{ url('/') }}" class="wt-btn wt-btn--solid wt-doc__back">Scan a wallet</a>
    </div>
</x-scan-layout>
