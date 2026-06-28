/**
 * We Watch Your Wallet — scan loading overlay.
 * On a scan trigger (home form submit, Re-scan link), show an indeterminate
 * branded overlay so the GoPlus round-trip doesn't look frozen. Full-page
 * navigation replaces it when the result loads; pageshow hides any bfcache leftover.
 * Honest: no fake progress bar — a single external call has no real %.
 */
(function ()
{
    'use strict';

    var ADDR_RE = /0x[0-9a-fA-F]{40}/;

    var overlay = document.getElementById('wt-loading');

    if (!overlay)
    {
        return;
    }

    var addrEl = document.getElementById('wt-loading-addr');

    function shortAddr(value)
    {
        var match = ADDR_RE.exec(value || '');
        return match ? (match[0].slice(0, 6) + '…' + match[0].slice(-4)) : '';
    }

    function show(address)
    {
        if (addrEl)
        {
            var shortened = shortAddr(address);

            if (shortened)
            {
                addrEl.textContent = 'Scanning ' + shortened;
                addrEl.hidden = false;
            }
            else
            {
                addrEl.hidden = true;
            }
        }

        overlay.classList.add('is-on');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function hide()
    {
        overlay.classList.remove('is-on');
        overlay.setAttribute('aria-hidden', 'true');
    }

    var MIN_MS = 800;        // 캐시 히트(즉시 응답)에도 진행 상태를 잠깐 보여주는 최소 노출 시간
    var navigating = false;

    function go(navigate, address)
    {
        if (navigating)
        {
            return;
        }

        navigating = true;
        show(address);
        window.setTimeout(navigate, MIN_MS);
    }

    var triggers = document.querySelectorAll('[data-scan-loading]');

    triggers.forEach(function (el)
    {
        if (el.tagName === 'FORM')
        {
            el.addEventListener('submit', function (e)
            {
                var input = el.querySelector('input[name="address"]');

                // 유효 주소처럼 보일 때만 가로채 오버레이(빈/잘못된 입력은 그대로 제출 → 서버 오류).
                if (!input || !ADDR_RE.test(input.value))
                {
                    return;
                }

                e.preventDefault();

                var button = el.querySelector('button[type="submit"]');
                if (button)
                {
                    button.disabled = true;
                    button.textContent = 'Scanning…';
                }

                go(function () { el.submit(); }, input.value);
            });
        }
        else
        {
            el.addEventListener('click', function (e)
            {
                // 새 탭/수정키 클릭(⌘/Ctrl/Shift/가운데 버튼)은 기본 동작 유지
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1)
                {
                    return;
                }

                e.preventDefault();
                var href = el.getAttribute('href');
                go(function () { window.location.assign(href); }, el.getAttribute('data-addr') || '');
            });
        }
    });

    // 뒤로가기(bfcache 복원) 시 오버레이가 남아있지 않도록.
    window.addEventListener('pageshow', hide);
})();
