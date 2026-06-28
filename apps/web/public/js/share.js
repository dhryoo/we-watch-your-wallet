/**
 * We Watch Your Wallet — copy-link share button (progressive enhancement).
 * The X / Farcaster links are plain anchors and work with JS off; this only adds
 * the "Copy link" clipboard behavior.
 */
(function ()
{
    'use strict';

    document.querySelectorAll('[data-share-copy]').forEach(function (btn)
    {
        btn.addEventListener('click', function ()
        {
            var url = btn.getAttribute('data-share-copy');

            if (!navigator.clipboard)
            {
                return;
            }

            navigator.clipboard.writeText(url).then(function ()
            {
                var prev = btn.textContent;
                btn.textContent = 'Copied';
                window.setTimeout(function () { btn.textContent = prev; }, 1500);
            }).catch(function () {});
        });
    });
})();
