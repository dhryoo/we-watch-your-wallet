@php $top = $result['risks'][0]; @endphp
{{-- 무료 모니터링 알림 이메일 템플릿(로컬 프리뷰 전용 — 메일러 미구현). 이메일 클라이언트 호환 위해 인라인 스타일. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Wallet risk scan summary</title>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600&family=DM+Sans:wght@500&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body style="margin:0;background:#f4f7f5;font-family:'Work Sans',sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;color:#f4f7f5;">Includes an unlimited USDC approval. Review and revoke yourself if needed.</div>
    <div style="max-width:660px;margin:0 auto;padding:24px;box-sizing:border-box;">
        <div style="background:#fff;border-radius:16px;overflow:hidden;border:1px solid #e6e6e3;">
            <div style="background:#a0f1bd;padding:22px 28px;display:flex;align-items:center;gap:11px;">
                <div style="width:22px;height:22px;border-radius:6px;background:#2e4f21;display:flex;align-items:center;justify-content:center;">
                    <div style="width:8px;height:8px;border-radius:50%;border:2px solid #a0f1bd;"></div>
                </div>
                <span style="font-family:'Work Sans';font-weight:600;font-size:17px;letter-spacing:-.04em;color:#2e4f21;">We Watch Your Wallet</span>
            </div>

            <div style="padding:30px 28px;">
                <h1 style="margin:0 0 8px;font-family:'Work Sans';font-weight:400;font-size:27px;letter-spacing:-.05em;color:#2e4f21;line-height:1.15;">Wallet risk scan summary</h1>
                <p style="margin:0 0 22px;font-family:'Work Sans';font-size:14px;letter-spacing:-.02em;color:#7d9276;">Ethereum · {{ $shortAddress }} · {{ $scannedAt }}</p>

                <div style="display:flex;gap:12px;margin-bottom:24px;">
                    <div style="flex:1;background:#fbeae8;border:1px solid #efb5b0;border-radius:13px;padding:16px;">
                        <div style="font-family:'Roboto Mono',monospace;font-size:10px;letter-spacing:.05em;color:#a05a52;margin-bottom:8px;">OVERALL RISK</div>
                        <div style="display:flex;align-items:baseline;gap:8px;">
                            <span style="font-family:'Work Sans';font-weight:300;font-size:34px;letter-spacing:-.05em;color:#2e4f21;">{{ $result['score'] }}</span>
                            <span style="display:inline-flex;align-items:center;gap:5px;"><span style="color:#b3382e;font-size:11px;">▲</span><span style="font-family:'Work Sans';font-weight:600;font-size:13px;color:#8e2018;">URGENT</span></span>
                        </div>
                    </div>
                    <div style="flex:1;background:#f9f9f9;border:1px solid #e6e6e3;border-radius:13px;padding:16px;">
                        <div style="font-family:'Roboto Mono',monospace;font-size:10px;letter-spacing:.05em;color:#9aa897;margin-bottom:8px;">RISKY APPROVALS</div>
                        <div style="font-family:'Work Sans';font-weight:300;font-size:34px;letter-spacing:-.05em;color:#2e4f21;">{{ count($result['risks']) }}</div>
                    </div>
                </div>

                <div style="font-family:'Work Sans';font-weight:600;font-size:12px;color:#2e4f21;margin-bottom:10px;">Top risk</div>
                <div style="border:1px solid #efb5b0;border-radius:13px;overflow:hidden;margin-bottom:24px;">
                    <div style="background:#fbeae8;padding:13px 16px;display:flex;align-items:center;justify-content:space-between;">
                        <div style="display:flex;align-items:center;gap:9px;">
                            <span style="font-family:'Work Sans';font-weight:600;font-size:14px;letter-spacing:-.03em;color:#2e4f21;">{{ $top['token'] }}</span>
                            <span style="font-family:'Roboto Mono',monospace;font-weight:500;font-size:10px;letter-spacing:.04em;color:#8e2018;background:#fff;border:1px solid #efb5b0;border-radius:5px;padding:3px 7px;">{{ $top['spenderTag'] }}</span>
                        </div>
                        <span style="display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #efb5b0;border-radius:40px;padding:5px 11px;"><span style="color:#b3382e;font-size:10px;">▲</span><span style="font-family:'Work Sans';font-weight:600;font-size:10.5px;color:#8e2018;">URGENT</span></span>
                    </div>
                    <div style="padding:14px 16px;">
                        <div style="margin-bottom:10px;"><span style="background:#fbeae8;border:1px solid #efb5b0;border-radius:6px;padding:4px 9px;font-family:'Work Sans';font-weight:600;font-size:11px;color:#8e2018;">Unlimited</span></div>
                        <p style="margin:0;font-family:'Work Sans';font-size:13px;line-height:1.5;letter-spacing:-.02em;color:#3c4a36;">{{ $top['explain'] }}</p>
                    </div>
                </div>

                <a href="{{ $revokeUrl }}" target="_blank" rel="noopener noreferrer" style="text-decoration:none;display:flex;align-items:center;justify-content:center;gap:8px;background:#2e4f21;color:#fff;border-radius:40px;padding:15px;font-family:'DM Sans';font-weight:500;font-size:15px;letter-spacing:-.01em;margin-bottom:10px;">Open revoke page <span style="font-size:13px;">↗</span></a>
                <p style="margin:0 0 24px;text-align:center;font-family:'Roboto Mono',monospace;font-size:10px;line-height:1.5;color:#9aa897;">Revoking happens in your own wallet — we never do it for you.</p>

                <div style="background:#ecfcf2;border:1px solid #cfe9d9;border-radius:14px;padding:20px;text-align:center;">
                    <div style="font-family:'Work Sans';font-weight:400;font-size:19px;letter-spacing:-.04em;color:#2e4f21;margin-bottom:6px;">Free continuous monitoring</div>
                    <p style="margin:0;font-family:'Work Sans';font-size:12.5px;line-height:1.5;letter-spacing:-.02em;color:#50624a;">We'll email you the moment a new risk is detected for this wallet. We never handle your keys or funds. Coming soon.</p>
                </div>
            </div>

            <div style="background:#2e4f21;padding:22px 28px;">
                <p style="margin:0;font-family:'Roboto Mono',monospace;font-size:10px;line-height:1.7;color:#9bbf9f;">This information is for informational purposes only and is not investment or trading advice. All actions, including revoke, are performed by you directly in your own wallet, and RPC data may be delayed or incorrect.</p>
                <p style="margin:10px 0 0;font-family:'Roboto Mono',monospace;font-size:10px;color:#6f9473;">Unsubscribe · Notification settings · We Watch Your Wallet</p>
            </div>
        </div>
    </div>
</body>
</html>
