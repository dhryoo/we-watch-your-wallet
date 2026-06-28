/**
 * We Watch Your Wallet — wallet-address QR scanner (progressive enhancement).
 * getUserMedia + jsQR(self-hosted). Decode is 100% client-side; only the resulting
 * 0x address text is placed in the input. Photo-upload fallback for blocked cameras.
 * No auto-submit (Turnstile-compatible) — fills the input and lets the user press Scan.
 */
(function ()
{
    'use strict';

    var ADDR_RE = /0x[0-9a-fA-F]{40}(?![0-9a-fA-F])/;

    var openBtn = document.getElementById('wt-qr-open');
    var overlay = document.getElementById('wt-qr-overlay');
    var video = document.getElementById('wt-qr-video');
    var canvas = document.getElementById('wt-qr-canvas');
    var cancelBtn = document.getElementById('wt-qr-cancel');
    var msg = document.getElementById('wt-qr-msg');
    var fileInput = document.getElementById('wt-qr-file');
    var addressInput = document.getElementById('wt-address-input');

    if (!openBtn || !overlay || !addressInput || typeof jsQR !== 'function')
    {
        return;
    }

    var hasCamera = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
    var ctx = canvas ? canvas.getContext('2d', { willReadFrequently: true }) : null;
    var stream = null;
    var rafId = null;

    // 상태 칩 자식 노드 + 재인지 상태(스로틀).
    var msgLead = msg ? msg.querySelector('.wt-qr-msg__lead') : null;
    var msgText = msg ? msg.querySelector('.wt-qr-msg__text') : null;
    var msgIcon = msg ? msg.querySelector('.wt-qr-msg__icon span') : null;
    var msgCount = msg ? msg.querySelector('.wt-qr-msg__count') : null;
    var msgSr = msg ? msg.querySelector('.wt-qr-msg__sr') : null;
    var lastKey = '';
    var repeats = 1;
    var lastPulse = 0;
    var msgSeq = 0;
    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    // jsQR 로드 시 스캔 버튼 노출(카메라 없으면 사진 업로드로 폴백).
    openBtn.hidden = false;

    /**
     * 상태 칩 피드백. state: 'info'(차분한 민트) | 'rejected'(앰버 — ETH 주소 아님).
     * tick() 이 매 프레임 호출하므로 펄스·반복카운트를 ~700ms 스로틀(스트로브·카운트 폭주 방지).
     */
    function setMsg(text, state)
    {
        if (!msg)
        {
            return;
        }

        if (!text)
        {
            msg.setAttribute('data-state', 'idle');
            if (msgLead) { msgLead.textContent = ''; }
            if (msgText) { msgText.textContent = ''; }
            if (msgCount) { msgCount.hidden = true; }
            if (msgSr) { msgSr.textContent = ''; }
            lastKey = '';
            repeats = 1;
            return;
        }

        state = state || 'info';
        msg.setAttribute('data-state', state);
        if (msgIcon) { msgIcon.textContent = (state === 'rejected') ? '!' : ''; }
        if (msgLead) { msgLead.textContent = (state === 'rejected') ? 'Not an Ethereum address' : ''; }
        if (msgText) { msgText.textContent = text; }

        var key = state + '|' + text;
        var changed = (key !== lastKey);
        var now = (window.performance && performance.now) ? performance.now() : Date.now();

        if (changed)
        {
            repeats = 1;
            lastKey = key;
        }
        else if (now - lastPulse > 700)
        {
            // 같은 결과를 계속 들고 있으면 ~1초에 한 번만 카운트(매 프레임 폭주 방지).
            repeats += 1;
        }

        if (changed || now - lastPulse > 700)
        {
            lastPulse = now;

            if (msgCount)
            {
                if (repeats > 1)
                {
                    msgCount.hidden = false;
                    msgCount.textContent = '×' + repeats;
                }
                else
                {
                    msgCount.hidden = true;
                }
            }

            // 같은 문구라도 스크린리더가 재낭독하도록 시퀀스 스탬프.
            msgSeq += 1;
            if (msgSr)
            {
                msgSr.textContent = (state === 'rejected' ? 'Not an Ethereum address. ' : '') + text + ' (' + msgSeq + ')';
            }

            if (!reduceMotion)
            {
                msg.classList.remove('is-pulse');
                void msg.offsetWidth; // reflow → 동일 결과에도 펄스 재생
                requestAnimationFrame(function ()
                {
                    msg.classList.add('is-pulse');
                });
            }
        }
    }

    function fill(text)
    {
        var match = ADDR_RE.exec(text || '');

        if (!match)
        {
            return false;
        }

        addressInput.value = match[0];
        close();
        addressInput.focus();

        return true;
    }

    function tick()
    {
        if (!stream)
        {
            return;
        }

        if (video.readyState === video.HAVE_ENOUGH_DATA && ctx)
        {
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            var frame = ctx.getImageData(0, 0, canvas.width, canvas.height);
            var code = jsQR(frame.data, frame.width, frame.height, { inversionAttempts: 'dontInvert' });

            if (code && code.data)
            {
                if (fill(code.data))
                {
                    return;
                }

                setMsg('This QR holds a different chain — Ethereum addresses start with 0x.', 'rejected');
            }
        }

        rafId = requestAnimationFrame(tick);
    }

    function open()
    {
        overlay.hidden = false;
        setMsg('');

        if (!hasCamera)
        {
            setMsg('Camera not available — use “Upload a photo” below.', 'info');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function (s)
            {
                stream = s;
                video.srcObject = s;
                video.setAttribute('playsinline', 'true');
                return video.play();
            })
            .then(function ()
            {
                rafId = requestAnimationFrame(tick);
            })
            .catch(function ()
            {
                setMsg('Couldn’t open the camera. Grant permission, or upload a photo below.', 'info');
            });
    }

    function stop()
    {
        if (rafId)
        {
            cancelAnimationFrame(rafId);
            rafId = null;
        }

        if (stream)
        {
            stream.getTracks().forEach(function (track)
            {
                track.stop();
            });
            stream = null;
        }

        if (video)
        {
            video.srcObject = null;
        }
    }

    function close()
    {
        overlay.hidden = true;
        stop();
    }

    openBtn.addEventListener('click', open);

    if (cancelBtn)
    {
        cancelBtn.addEventListener('click', close);
    }

    overlay.addEventListener('click', function (e)
    {
        if (e.target === overlay)
        {
            close();
        }
    });

    if (fileInput && ctx)
    {
        fileInput.addEventListener('change', function ()
        {
            var file = fileInput.files && fileInput.files[0];

            if (!file)
            {
                return;
            }

            var img = new Image();

            img.onload = function ()
            {
                canvas.width = img.naturalWidth;
                canvas.height = img.naturalHeight;
                ctx.drawImage(img, 0, 0);

                var data = ctx.getImageData(0, 0, canvas.width, canvas.height);
                var code = jsQR(data.data, data.width, data.height);
                URL.revokeObjectURL(img.src);

                if (code && code.data && fill(code.data))
                {
                    return;
                }

                setMsg('No Ethereum address found in that image.', 'rejected');
            };

            img.src = URL.createObjectURL(file);
        });
    }
})();
