{{-- OG 공유 카드 1200×630. 민감정보 금지(전체 주소·토큰 상세 노출 안 함). 실서비스는 Satori 등으로 PNG 렌더 권장. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="{{ asset('css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/watchtower.css') }}">
    <style>body{ background:#e7e5df; display:flex; align-items:flex-start; justify-content:center; padding:40px; }</style>
</head>
<body>
    <div style="width:1200px;height:630px;background:#2e4f21;overflow:hidden;position:relative;display:flex;flex-direction:column;padding:64px 72px;box-sizing:border-box;">
        <div style="position:absolute;top:-120px;right:-120px;width:420px;height:420px;border-radius:50%;background:#3a6029;"></div>
        <div style="position:absolute;top:-40px;right:-40px;width:260px;height:260px;border-radius:50%;background:#456f31;"></div>

        <div style="position:relative;display:flex;align-items:center;gap:14px;">
            <div style="width:34px;height:34px;border-radius:9px;background:#a0f1bd;display:flex;align-items:center;justify-content:center;">
                <div style="width:12px;height:12px;border-radius:50%;border:3px solid #2e4f21;"></div>
            </div>
            <span style="font-family:'Work Sans';font-weight:600;font-size:26px;letter-spacing:-.04em;color:#fff;">We Watch Your Wallet</span>
            <span style="font-family:'Roboto Mono',monospace;font-size:13px;letter-spacing:.06em;color:#9bbf9f;margin-left:6px;">Wallet risk scan</span>
        </div>

        @if ($result['failed'] ?? false)
            {{-- 정직성: 실패한 스캔을 "0 found"로 공유하지 않는다(가짜 clean 금지). --}}
            <div style="position:relative;flex:1;display:flex;flex-direction:column;justify-content:center;">
                <div style="font-family:'Work Sans';font-weight:200;font-size:68px;line-height:1.05;letter-spacing:-.05em;color:#fff;margin-bottom:20px;">Scan unavailable<br><span style="font-weight:400;font-size:42px;">please try again</span></div>
                <div style="font-family:'Work Sans';font-weight:500;font-size:21px;letter-spacing:-.02em;color:#cdeccf;max-width:760px;">We couldn't fetch on-chain data — this is <span style="color:#ffd9d4;">not</span> a clean-wallet result.</div>
            </div>
        @else
            <div style="position:relative;flex:1;display:flex;align-items:center;justify-content:space-between;gap:56px;">
                <div style="flex:1;">
                    <div style="display:inline-flex;align-items:center;gap:9px;background:rgba(160,241,189,.16);border:1px solid #4f7a3e;border-radius:40px;padding:8px 16px;margin-bottom:26px;">
                        <div style="width:9px;height:9px;border-radius:50%;background:#a0f1bd;"></div>
                        <span style="font-family:'Work Sans';font-weight:500;font-size:16px;letter-spacing:-.02em;color:#cdeccf;">{{ $chainLabel ?? 'Ethereum' }} · {{ $shortAddress }}</span>
                    </div>
                    @php $sev = \App\Scan\Severity::tokens($result['severity']); @endphp
                    <div style="font-family:'Work Sans';font-weight:200;font-size:76px;line-height:1.0;letter-spacing:-.05em;color:#fff;margin-bottom:18px;">Risky approvals<br><span style="font-weight:400;">{{ count($result['risks']) }} found</span></div>
                    <div style="display:inline-flex;align-items:center;gap:10px;background:{{ $sev['bg'] }};border-radius:40px;padding:10px 20px;">
                        <span style="color:{{ $sev['ic'] }};font-size:15px;">{{ $sev['mark'] }}</span>
                        <span style="font-family:'Work Sans';font-weight:600;font-size:18px;letter-spacing:.01em;color:{{ $sev['fg'] }};">{{ $sev['label'] }}</span>
                    </div>
                </div>
                <div style="flex:none;">
                    <x-score-gauge :severity="$result['severity']" :score="$result['score']" :size="210" :dark="true" :scoreSize="84" unit="Risk score / 100" :unitSize="14" />
                </div>
            </div>
        @endif

        <div style="position:relative;display:flex;align-items:center;justify-content:space-between;border-top:1px solid #456f31;padding-top:26px;">
            <span style="font-family:'Work Sans';font-weight:500;font-size:24px;letter-spacing:-.03em;color:#fff;">Scan your wallet free <span style="color:#a0f1bd;">→</span></span>
            <span style="font-family:'Roboto Mono',monospace;font-size:13px;letter-spacing:.04em;color:#7fa783;">wewatchyourwallet.com · non-custodial · read-only</span>
        </div>
    </div>
</body>
</html>
