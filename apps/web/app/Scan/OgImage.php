<?php

namespace App\Scan;

/**
 * OG 공유 카드(1200×630)를 GD로 직접 렌더 → PNG 바이트. 새 서버 의존성 없음(GD+FreeType).
 * 브랜드 팔레트/레이아웃을 og.blade.php에 근접 재현. 정직성: 실패 스캔은 "Scan unavailable" 카드(가짜 clean 금지).
 */
class OgImage
{
    private const W = 1200;
    private const H = 630;
    private const PADX = 72;

    private string $font;

    public function __construct(?string $fontPath = null)
    {
        $this->font = $fontPath ?? resource_path('fonts/WorkSans-Variable.ttf');
    }

    /** @param array<string,mixed> $result @return string PNG bytes */
    public function render(array $result, string $shortAddress, string $chainLabel = 'Ethereum'): string
    {
        $im = imagecreatetruecolor(self::W, self::H);
        imagealphablending($im, true);
        imagefill($im, 0, 0, $this->c($im, '#2E4F21'));

        // 우상단 장식 원
        imagefilledellipse($im, 1110, 90, 420, 420, $this->c($im, '#3A6029'));
        imagefilledellipse($im, 1110, 90, 260, 260, $this->c($im, '#456F31'));

        $this->header($im);

        if ($result['failed'] ?? false)
        {
            $this->unavailableBody($im);
        }
        else
        {
            $this->resultBody($im, $result, $shortAddress, $chainLabel);
        }

        $this->footer($im);

        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return $png;
    }

    /** 홈/문서 페이지 공유용 일반 브랜드 카드(지갑/게이지 없는 히어로). @return string PNG bytes */
    public function brandCard(): string
    {
        $im = imagecreatetruecolor(self::W, self::H);
        imagealphablending($im, true);
        imagefill($im, 0, 0, $this->c($im, '#2E4F21'));
        imagefilledellipse($im, 1110, 90, 420, 420, $this->c($im, '#3A6029'));
        imagefilledellipse($im, 1110, 90, 260, 260, $this->c($im, '#456F31'));

        $this->header($im);
        $this->text($im, 62, self::PADX, 300, '#FFFFFF', 'Scan your wallet for');
        $this->text($im, 62, self::PADX, 376, '#FFFFFF', 'risky token approvals', true);
        $this->text($im, 22, self::PADX, 434, '#A0F1BD', 'Free · non-custodial · read-only · 5 chains');
        $this->footer($im);

        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return $png;
    }

    /** 정사각 브랜드 아이콘(apple-touch-icon 등). @return string PNG bytes */
    public function brandIcon(int $size): string
    {
        $im = imagecreatetruecolor($size, $size);
        imagealphablending($im, true);
        imagefill($im, 0, 0, $this->c($im, '#2E4F21'));

        $pad = (int) round($size * 0.16);
        $this->roundedRect($im, $pad, $pad, $size - $pad, $size - $pad, (int) round($size * 0.20), $this->c($im, '#A0F1BD'));

        $cx = (int) round($size / 2);
        imagefilledellipse($im, $cx, $cx, (int) round($size * 0.36), (int) round($size * 0.36), $this->c($im, '#2E4F21'));
        imagefilledellipse($im, $cx, $cx, (int) round($size * 0.18), (int) round($size * 0.18), $this->c($im, '#A0F1BD'));

        ob_start();
        imagepng($im);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return $png;
    }

    private function header($im): void
    {
        // 로고 칩 + 그린 링
        $this->roundedRect($im, self::PADX, 64, self::PADX + 34, 98, 9, $this->c($im, '#A0F1BD'));
        imagefilledellipse($im, self::PADX + 17, 81, 13, 13, $this->c($im, '#2E4F21'));
        imagefilledellipse($im, self::PADX + 17, 81, 6, 6, $this->c($im, '#A0F1BD'));

        $brandX = self::PADX + 34 + 14;
        $this->text($im, 26, $brandX, 91, '#FFFFFF', 'We Watch Your Wallet', true);
        $kickerX = $brandX + $this->textW(26, 'We Watch Your Wallet', true) + 16;
        $this->text($im, 13, $kickerX, 89, '#9BBF9F', 'Wallet risk scan');
    }

    /** @param array<string,mixed> $result */
    private function resultBody($im, array $result, string $shortAddress, string $chainLabel = 'Ethereum'): void
    {
        // 체인/주소 칩
        $chipText = $chainLabel . ' · ' . $shortAddress;
        $chipW = 18 + 9 + 11 + $this->textW(16, $chipText) + 18;
        $this->roundedRect($im, self::PADX, 168, self::PADX + (int) $chipW, 208, 20, $this->c($im, '#3A6029'));
        imagefilledellipse($im, self::PADX + 18 + 4, 188, 9, 9, $this->c($im, '#A0F1BD'));
        $this->text($im, 16, self::PADX + 18 + 9 + 11, 194, '#CDECCF', $chipText);

        // 헤드라인
        $this->text($im, 58, self::PADX, 312, '#FFFFFF', 'Risky approvals');
        $this->text($im, 58, self::PADX, 384, '#FFFFFF', count($result['risks']) . ' found', true);

        // severity 배지
        $sev = strtoupper((string) $result['severity']);
        $accent = $sev === 'INFO' ? '#A0F1BD' : Severity::tokens($sev)['ic'];
        $label = Severity::tokens($sev)['label'];
        $badgeText = $this->mark($sev) . '  ' . $label;
        $badgeW = 20 + $this->textW(18, $badgeText, true) + 20;
        $this->roundedRect($im, self::PADX, 416, self::PADX + (int) $badgeW, 458, 21, $this->c($im, '#26421B'));
        $this->text($im, 18, self::PADX + 20, 443, $accent, $badgeText, true);

        $this->gauge($im, 1000, 318, (int) $result['score'], $accent);
    }

    private function unavailableBody($im): void
    {
        $this->text($im, 64, self::PADX, 300, '#FFFFFF', 'Scan unavailable', true);
        $this->text($im, 40, self::PADX, 360, '#FFFFFF', 'please try again');
        $this->text($im, 21, self::PADX, 410, '#CDECCF', "We couldn't fetch on-chain data — not a clean-wallet result.");
    }

    private function gauge($im, int $cx, int $cy, int $score, string $accent): void
    {
        $score = max(0, min(100, $score));
        imagefilledellipse($im, $cx, $cy, 210, 210, $this->c($im, '#3A6029'));

        if ($score > 0)
        {
            $sweep = 360 * $score / 100;
            imagefilledarc($im, $cx, $cy, 210, 210, 270, (int) round(270 + $sweep), $this->c($im, $accent), IMG_ARC_PIE);
        }

        imagefilledellipse($im, $cx, $cy, 166, 166, $this->c($im, '#2E4F21'));

        $num = (string) $score;
        $this->text($im, 64, $cx - (int) ($this->textW(64, $num, true) / 2), $cy + 8, '#FFFFFF', $num, true);
        $unit = 'Risk score / 100';
        $this->text($im, 14, $cx - (int) ($this->textW(14, $unit) / 2), $cy + 48, '#9BBF9F', $unit);
    }

    private function footer($im): void
    {
        imagefilledrectangle($im, self::PADX, 540, self::W - self::PADX, 541, $this->c($im, '#456F31'));

        $endX = $this->text($im, 24, self::PADX, 582, '#FFFFFF', 'Scan your wallet free ');
        $this->text($im, 24, $endX, 582, '#A0F1BD', '→', true);

        $right = 'wewatchyourwallet.com · non-custodial · read-only';
        $this->text($im, 13, self::W - self::PADX - $this->textW(13, $right), 580, '#7FA783', $right);
    }

    // ── GD helpers ────────────────────────────────────────────────────

    private function mark(string $severity): string
    {
        return match ($severity)
        {
            'URGENT' => '▲',
            'WARN' => '◆',
            default => '●',
        };
    }

    /** baseline 좌표에 텍스트. bold=faux-bold(0.6px 겹쳐그리기). 반환: 텍스트 끝 x. */
    private function text($im, int $size, int $x, int $y, string $hex, string $str, bool $bold = false): int
    {
        $color = $this->c($im, $hex);
        $bbox = imagettftext($im, $size, 0, $x, $y, $color, $this->font, $str);

        if ($bold)
        {
            imagettftext($im, $size, 0, $x + 1, $y, $color, $this->font, $str);
        }

        return $bbox[2];
    }

    private function textW(int $size, string $str, bool $bold = false): int
    {
        $bbox = imagettfbbox($size, 0, $this->font, $str);

        return ($bbox[2] - $bbox[0]) + ($bold ? 1 : 0);
    }

    private function roundedRect($im, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($im, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($im, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    private function c($im, string $hex): int
    {
        $hex = ltrim($hex, '#');

        return imagecolorallocate(
            $im,
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        );
    }
}
