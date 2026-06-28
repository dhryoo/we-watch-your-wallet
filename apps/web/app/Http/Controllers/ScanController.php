<?php

namespace App\Http\Controllers;

use App\Scan\Address;
use App\Scan\MockScanData;
use App\Scan\ScanResult;
use App\Scan\ScanService;
use App\Scan\Turnstile;
use App\Scan\WalletAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScanController extends Controller
{
    private const CHAIN_ID = 1;
    private const ADDRESS_RE = '/^0x[0-9a-fA-F]{40}$/';

    public function __construct(private readonly ScanService $scans)
    {
    }

    /** 랜딩 — 주소 입력 + Turnstile 위젯 → POST /scan. */
    public function home(): View
    {
        return view('home', ['sitekey' => config('scan.turnstile.sitekey')]);
    }

    /** 스캔 트리거(인간 진입점) — Turnstile 검증 후 결과 페이지로. */
    public function store(Request $request, Turnstile $turnstile): RedirectResponse
    {
        $request->validate(['address' => ['required', 'string']]);

        // QR/붙여넣기 텍스트(EIP-681 ethereum:0x…@1 포함)에서 0x 주소 추출.
        $address = WalletAddress::extract((string) $request->input('address'));

        if ($address === null)
        {
            return back()->withErrors(['address' => 'Enter a valid 0x Ethereum address.'])->withInput();
        }

        if (!$turnstile->verify($request->input('cf-turnstile-response'), $request->ip()))
        {
            return back()->withErrors(['address' => 'Captcha verification failed. Please try again.'])->withInput();
        }

        return redirect('/scan/' . $address);
    }

    public function show(Request $request, string $address): View
    {
        if (!preg_match(self::ADDRESS_RE, $address))
        {
            abort(404);
        }

        // 디자인 미리보기(목업, GoPlus 미호출).
        if ($request->boolean('demo'))
        {
            $result = $request->query('state') === 'safe'
                ? MockScanData::safe($address, self::CHAIN_ID)
                : MockScanData::risky($address, self::CHAIN_ID);
            $result['risks'] = ScanResult::sortBySeverityDesc($result['risks']);

            return $this->render('scan.result', $address, $result);
        }

        $result = $this->scans->scan($address, self::CHAIN_ID, $request->ip());

        if ($result === null)
        {
            return $this->render('scan.limited', $address, $this->blank());
        }

        if ($result['failed'] ?? false)
        {
            return $this->render('scan.unavailable', $address, $result);
        }

        return $this->render($result['risks'] === [] ? 'scan.empty' : 'scan.result', $address, $result);
    }

    public function og(string $address): View
    {
        $result = $this->scans->scan($address, self::CHAIN_ID) ?? $this->blank();

        return view('scan.og', [
            'shortAddress' => Address::short($address),
            'result' => $result,
        ]);
    }

    public function email(string $address): View
    {
        $result = $this->scans->scan($address, self::CHAIN_ID);

        if ($result === null || ($result['failed'] ?? false) || $result['risks'] === [])
        {
            $result = MockScanData::risky($address, self::CHAIN_ID); // 데모 폴백
            $result['risks'] = ScanResult::sortBySeverityDesc($result['risks']);
        }

        return view('emails.scan-lead', [
            'shortAddress' => Address::short($address),
            'scannedAt' => gmdate('Y-m-d H:i', (int) ($result['scannedAt'] ?? time())) . ' UTC',
            'result' => $result,
            'revokeUrl' => MockScanData::revokeUrl($address, self::CHAIN_ID),
        ]);
    }

    /** @param array<string,mixed> $result */
    private function render(string $view, string $address, array $result): View
    {
        return view($view, [
            'address' => $address,
            'chainId' => self::CHAIN_ID,
            'maskedAddress' => Address::mask($address),
            'shortAddress' => Address::short($address),
            'scannedAt' => gmdate('Y-m-d H:i', (int) ($result['scannedAt'] ?? time())) . ' UTC',
            'result' => $result,
        ]);
    }

    /** @return array<string,mixed> */
    private function blank(): array
    {
        return ['score' => 0, 'severity' => 'WATCH', 'approvalsReviewed' => 0, 'stale' => true, 'failed' => true, 'risks' => []];
    }
}
