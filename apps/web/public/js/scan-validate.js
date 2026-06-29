/**
 * We Watch Your Wallet — client-side wallet-address pre-validation for the home scan form.
 *
 * Blocks submission of an obviously-invalid address BEFORE the page round-trips, so a typo
 * doesn't reload the page and reset the Turnstile widget (forcing the user to re-verify).
 * Mirrors the server's extraction regex exactly (App\Scan\WalletAddress::extract), so anything
 * accepted here the server also accepts — no client/server disagreement that would still reload.
 *
 * Progressive enhancement: loads before scan-loading.js so its submit guard runs first. With JS
 * off, nothing changes — the server still validates and shows the @error('address') message.
 */
(function ()
{
    'use strict';

    var form = document.querySelector('form[data-scan-loading]');

    if (!form)
    {
        return;
    }

    var input = form.querySelector('input[name="address"]');
    var error = document.getElementById('wt-address-error');

    if (!input || !error)
    {
        return;
    }

    // Same pattern as WalletAddress::extract: a 0x + 40 hex run not followed by another hex
    // char (so a 64-hex tx hash is rejected, and an EIP-681 "ethereum:0x…@1" paste still matches).
    var ADDR_RE = /0x[0-9a-fA-F]{40}(?![0-9a-fA-F])/;
    // Mirrors Ens::looksLikeName — an ENS .eth name (the server resolves it via RPC).
    var ENS_RE = /^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*\.eth$/;

    function isValid(value)
    {
        return ADDR_RE.test(value) || ENS_RE.test(value.trim().toLowerCase());
    }

    function clearError()
    {
        error.hidden = true;
        error.textContent = '';
        input.removeAttribute('aria-invalid');
    }

    form.addEventListener('submit', function (e)
    {
        if (isValid(input.value))
        {
            clearError();
            return; // valid → let scan-loading.js show the overlay and submit
        }

        // invalid → stop the submit (and scan-loading.js) so the page never reloads
        e.preventDefault();
        e.stopImmediatePropagation();

        error.textContent = 'Enter a valid 0x Ethereum address or ENS name.';
        error.hidden = false;
        input.setAttribute('aria-invalid', 'true');
        input.focus();
    });

    // Clear the message as soon as the user edits the field.
    input.addEventListener('input', clearError);
})();
