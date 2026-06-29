<?php

namespace App\Http\Controllers;

use App\Scan\Address;
use App\Scan\Chain;
use App\Scan\Ens;
use App\Scan\EnsException;
use App\Scan\EnsResolver;
use App\Scan\MockScanData;
use App\Scan\OgImage;
use App\Scan\ScanResult;
use App\Scan\ScanService;
use App\Scan\Turnstile;
use App\Scan\WalletAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
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
        return view('home', [
            'sitekey' => config('scan.turnstile.sitekey'),
            'chains' => Chain::all(),
        ]);
    }

    /** 스캔 트리거(인간 진입점) — Turnstile 검증 후 결과 페이지로. 0x 주소 또는 ENS(.eth) 이름 허용. */
    public function store(Request $request, Turnstile $turnstile, EnsResolver $ens): RedirectResponse
    {
        $request->validate(['address' => ['required', 'string', 'max:255']]);

        // 체인 선택(홈 드롭다운). 미지원/누락 시 ethereum으로 폴백.
        $chain = (string) $request->input('chain', Chain::DEFAULT);

        if (Chain::fromSlug($chain) === null)
        {
            $chain = Chain::DEFAULT;
        }

        $input = (string) $request->input('address');

        // QR/붙여넣기 텍스트(EIP-681 ethereum:0x…@1 포함)에서 0x 주소 추출.
        $address = WalletAddress::extract($input);

        // 0x도 ENS 이름꼴도 아니면 turnstile 소모 없이 즉시 형식 오류(오타가 캡차를 재요구하지 않게).
        if ($address === null && !Ens::looksLikeName($input))
        {
            return back()->withErrors(['address' => 'Enter a valid 0x Ethereum address or ENS name.'])->withInput();
        }

        if (!$turnstile->verify($request->input('cf-turnstile-response'), $request->ip()))
        {
            return back()->withErrors(['address' => 'Captcha verification failed. Please try again.'])->withInput();
        }

        // ENS 이름이면 해석(24h 캐시). 정직성: 일시적 실패는 재시도 안내, "주소 없음"과 구분.
        if ($address === null)
        {
            try
            {
                $address = $this->resolveEns($input, $ens);
            }
            catch (EnsException)
            {
                return back()->withErrors(['address' => "Couldn't resolve that ENS name right now. Try again, or paste the 0x address."])->withInput();
            }

            if ($address === null)
            {
                return back()->withErrors(['address' => 'That ENS name has no Ethereum address set.'])->withInput();
            }
        }

        return redirect(Chain::scanPath($chain, $address));
    }

    /** ENS 이름 → 0x 주소(성공 시에만 24h 캐시). 음수/예외는 캐시하지 않는다. */
    private function resolveEns(string $input, EnsResolver $ens): ?string
    {
        $name = strtolower(trim($input));
        $cached = Cache::get('ens:' . $name);

        if (is_string($cached))
        {
            return $cached;
        }

        $address = $ens->resolve($name);

        if ($address !== null)
        {
            Cache::put('ens:' . $name, $address, (int) config('scan.cache_ttl', 86400));
        }

        return $address;
    }

    public function show(Request $request): View
    {
        // 라우트 파라미터는 이름으로 읽는다(Laravel의 위치기반 주입을 피해 두 라우트 형태를 한 메서드로).
        $address = (string) $request->route('address');
        $chain = (string) $request->route('chain', Chain::DEFAULT);
        $chainId = $this->chainId($chain);

        if (!preg_match(self::ADDRESS_RE, $address))
        {
            abort(404);
        }

        // 디자인 미리보기(목업, GoPlus 미호출).
        if ($request->boolean('demo'))
        {
            $result = $request->query('state') === 'safe'
                ? MockScanData::safe($address, $chainId)
                : MockScanData::risky($address, $chainId);
            $result['risks'] = ScanResult::sortBySeverityDesc($result['risks']);

            return $this->render($result['risks'] === [] ? 'scan.empty' : 'scan.result', $chain, $address, $result);
        }

        $result = $this->scans->scan($address, $chainId, $request->ip());

        if ($result === null)
        {
            return $this->render('scan.limited', $chain, $address, $this->blank());
        }

        if ($result['failed'] ?? false)
        {
            return $this->render('scan.unavailable', $chain, $address, $result);
        }

        return $this->render($result['risks'] === [] ? 'scan.empty' : 'scan.result', $chain, $address, $result);
    }

    public function og(Request $request): View
    {
        $address = (string) $request->route('address');
        $chain = (string) $request->route('chain', Chain::DEFAULT);
        $chainId = $this->chainId($chain);

        if (!preg_match(self::ADDRESS_RE, $address))
        {
            abort(404);
        }

        $result = $this->scans->scan($address, $chainId, $request->ip()) ?? $this->blank();

        return view('scan.og', [
            'shortAddress' => Address::short($address),
            'chainLabel' => Chain::labelFor($chainId),
            'result' => $result,
        ]);
    }

    /** OG 공유 이미지(PNG) — 소셜 크롤러가 og:image로 가져간다. 결과 지문 기준 24h 캐시. 실패 시 정직한 unavailable 카드. */
    public function ogImage(Request $request, OgImage $og): Response
    {
        $address = (string) $request->route('address');
        $chain = (string) $request->route('chain', Chain::DEFAULT);
        $chainId = $this->chainId($chain);

        if (!preg_match(self::ADDRESS_RE, $address))
        {
            abort(404);
        }

        $result = $this->scans->scan($address, $chainId, $request->ip()) ?? $this->blank();

        $fingerprint = md5(implode('|', [
            strtolower($address),
            (string) $chainId,
            (string) ($result['severity'] ?? ''),
            (string) ($result['score'] ?? ''),
            (string) count($result['risks'] ?? []),
            ($result['failed'] ?? false) ? 'f' : 'k',
        ]));

        $png = Cache::remember(
            'og:png:' . $fingerprint,
            (int) config('scan.cache_ttl', 86400),
            fn () => $og->render($result, Address::short($address), Chain::labelFor($chainId)),
        );

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** 체인 slug → chainId. 미지원이면 404(라우트 제약과 이중 안전장치). */
    private function chainId(string $chain): int
    {
        $resolved = Chain::fromSlug($chain);

        if ($resolved === null)
        {
            abort(404);
        }

        return $resolved['id'];
    }

    /** 이메일 템플릿 미리보기 — 로컬 전용. 메일러 미구현이라 공개하면 실주소에 가짜 데이터가 노출되므로 프로덕션 404. */
    public function email(string $address): View
    {
        abort_unless(app()->environment('local'), 404);

        if (!preg_match(self::ADDRESS_RE, $address))
        {
            abort(404);
        }

        // 로컬 템플릿 프리뷰 전용 — 항상 목업으로 렌더. 템플릿이 URGENT/Unlimited를 하드코딩하므로
        // 실주소의 실데이터를 바인딩하면 거짓 위험이 될 수 있다(스캔 호출도 안 함).
        $result = MockScanData::risky($address, self::CHAIN_ID);
        $result['risks'] = ScanResult::sortBySeverityDesc($result['risks']);

        return view('emails.scan-lead', [
            'shortAddress' => Address::short($address),
            'scannedAt' => gmdate('Y-m-d H:i', (int) ($result['scannedAt'] ?? time())) . ' UTC',
            'result' => $result,
            'revokeUrl' => MockScanData::revokeUrl($address, self::CHAIN_ID),
        ]);
    }

    /** @param array<string,mixed> $result */
    private function render(string $view, string $chain, string $address, array $result): View
    {
        $chainId = $this->chainId($chain);

        return view($view, [
            'address' => $address,
            'chainId' => $chainId,
            'chainSlug' => $chain,
            'chainLabel' => Chain::labelFor($chainId),
            'scanBase' => Chain::scanPath($chain, $address),
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
