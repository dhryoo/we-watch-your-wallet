<x-scan-layout title="Approval Phishing: How Wallet Drainers Actually Work" description="Approval phishing lets wallet drainers steal tokens without your seed phrase. Learn how the scam works, red flags before signing, and how to clean up.">
    <div class="wt-doc">
        <h1>Approval Phishing: How Wallet Drainers Actually Work</h1>
        <p class="wt-doc__lead">How wallet drainers use approval phishing to steal tokens without your seed phrase — the lures, the signatures to watch for, and how to clean up afterward.</p>

        <h2>Drainers don't need your seed phrase</h2>
        <p>Most people picture crypto theft as someone stealing a seed phrase. Approval phishing works differently. The scammer never sees your keys. Instead, they trick you into signing something — a transaction or a message — that grants an address they control permission to move tokens out of your wallet. You do the signing yourself, in your own wallet, usually on a site that looks legitimate.</p>
        <p>This works because tokens on EVM chains like Ethereum, Base, Arbitrum, Polygon, and BNB Chain support approvals. An approval lets another address spend a token on your behalf. Decentralized exchanges and NFT marketplaces request approvals every day for normal reasons. Drainers abuse the exact same mechanism, which is why the prompt can look routine. Technically, it is routine — the problem is who you are granting it to.</p>

        <h2>The common lures</h2>
        <p>Drainer kits are sold as ready-made tooling, so the same tricks appear again and again. The goal is always identical: get you onto a page that requests an approval or a permit signature while you believe you are doing something else.</p>
        <ul>
            <li>Fake mints: a hyped NFT mint page where the mint button actually requests setApprovalForAll on an NFT collection you already own — and may prompt again for each valuable collection in your wallet.</li>
            <li>Airdrop claims: a site says you have unclaimed tokens, but the claim button asks you to grant an approval instead of sending you anything.</li>
            <li>Fake support: someone posing as support staff in a chat or DM walks you to a verification or wallet-sync page that requests signatures.</li>
            <li>Lookalike sites: a copy of a real project's site, reached through a sponsored search result, a typo domain, or a link posted from a hacked account.</li>
        </ul>

        <h2>Why the theft can happen much later</h2>
        <p>An approval is standing permission, not a one-time action. Once you sign it, it stays in force until you revoke it — or, if it was for a limited amount, until that amount is used up. The unlimited approvals drainers ask for never run out on their own. The drainer does not have to move anything right away.</p>
        <p>Many operators wait. They may empty the wallet minutes later, or watch it for weeks and act when the balance grows or a valuable token arrives. That gap is why victims often never connect the loss to the signature that caused it — the phishing site is long forgotten by the time the tokens move.</p>

        <h2>increaseAllowance, permit, and Permit2, in plain words</h2>
        <p>A few prompts deserve extra attention because they don't contain the word approve.</p>
        <p>increaseAllowance adds to a spending allowance. If a prompt shows this function, it is still an approval — it raises how much a spender can take from your wallet, and it can create a new allowance even if your current one is zero.</p>
        <p>permit is a signature, not a transaction. It costs you no gas and nothing appears on-chain when you sign it, which makes it feel harmless. But a permit signature can authorize spending just like an approval: the scammer submits it on-chain later, paying the gas themselves, and gains the same access. If a site asks you to sign a typed message that mentions a spender, an amount, and a deadline, treat it exactly like an approval request.</p>
        <p>Permit2 is a shared contract that many trading apps use to manage approvals. If you have ever approved a token to Permit2, a Permit2 signature can hand that token's allowance to a new spender — again with no gas and no transaction from you. The same rule applies: a typed message with a spender field is an approval.</p>

        <h2>Red flags before you sign</h2>
        <p>Your wallet prompt is the last checkpoint. Slow down when you see these words: approve or increaseAllowance means you are granting token spending rights. setApprovalForAll means you are granting access to every NFT in a collection, not just one. permit, Permit2, or any typed-data signature with a spender field can grant the same access without a transaction from you.</p>
        <p>If anything feels off, reject the prompt. Closing the window costs you nothing.</p>
        <ul>
            <li>The amount is unlimited, or an enormous number you did not choose.</li>
            <li>The spender address is a contract you cannot identify.</li>
            <li>You expected to receive something, but the prompt asks you to grant something.</li>
            <li>The site pushes urgency: countdowns, last-chance warnings, or verify-now messages.</li>
            <li>The URL is slightly different from the project's real domain.</li>
        </ul>

        <h2>Cleanup after a suspected phish</h2>
        <p>If you think you signed something bad, check your approvals. A read-only approval scanner like ours can list the active approvals on your address across chains — no wallet connection or signature needed, just paste the address. Look for spenders you don't recognize, unlimited amounts, and anything granted around the time of the suspicious signature.</p>
        <p>One limit to know: a permit or Permit2 signature the scammer has not yet used is invisible to every scanner, ours included, because nothing is on-chain yet. It only shows up as a normal allowance once the scammer submits it. If you signed a typed message on a suspicious site, re-check your approvals over the following days and revoke the allowance if it appears.</p>
        <p>Then revoke what you don't recognize. Revoking happens in your own wallet at a tool like revoke.cash: each revoke is a small transaction you sign yourself and pay a small gas fee for, and it removes that spender's permission going forward. It cannot bring back anything already taken, but it stops that spender from taking more.</p>
        <p>Finally, know the difference between a bad approval and a leaked key. If you only signed an approval or a permit, your keys were never exposed — revoking removes the permission, and the wallet is still yours to use. But if you typed your seed phrase or private key into a website, a chat, or a so-called validation app, the key itself is compromised. Revoking cannot help there, because the thief can sign anything as you. In that case, move remaining assets to a brand-new wallet and stop using the old one.</p>

        <h2>Frequently asked questions</h2>
        <p><strong>Can a drainer steal my tokens without my seed phrase?</strong><br>Yes. An approval or permit signature grants permission to move specific tokens. The thief never needs your keys — the permission you signed does the work.</p>
        <p><strong>I signed something on a suspicious site but nothing was stolen. Am I safe?</strong><br>Not necessarily. An approval stays valid until you revoke it, and drainers often wait before acting. A permit signature will not even appear in approval lists until the scammer uses it. Check your approvals now, revoke anything you don't recognize, and re-check over the next few days.</p>
        <p><strong>Does revoking an approval recover stolen tokens?</strong><br>No. Revoking removes the permission going forward, so that spender cannot take more. Tokens that were already moved cannot be recovered by revoking.</p>
        <p><strong>When do I actually need a new wallet?</strong><br>Only if the key itself was exposed — for example, you entered your seed phrase on a website. A bad approval alone can be cleaned up by revoking, and you can keep using the wallet.</p>

        <h2>Keep learning</h2>
        <ul>
            <li><a href="{{ url('/learn/what-is-a-token-approval') }}">What Is a Token Approval? Allowances Explained in Plain English</a></li>
            <li><a href="{{ url('/learn/how-to-revoke-token-approvals') }}">How to Revoke Token Approvals (Step by Step)</a></li>
            <li><a href="{{ url('/learn/unlimited-token-approvals') }}">Unlimited Token Approvals: What the Risk Actually Is</a></li>
            <li><a href="{{ url('/how-it-works') }}">How We Watch Your Wallet works</a></li>
        </ul>

        <a href="{{ url('/') }}" class="wt-btn wt-btn--solid wt-doc__back">Scan a wallet</a>
    </div>
</x-scan-layout>
