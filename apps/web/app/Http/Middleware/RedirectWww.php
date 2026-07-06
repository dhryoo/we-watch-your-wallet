<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 정본 호스트 통합: www.* 요청을 apex로 301 리다이렉트한다.
 * www와 apex가 둘 다 200을 반환하면 Google이 "표준 없는 중복 페이지"로 색인을 거부하므로,
 * 한 호스트(apex)로 합친다. 로컬(localhost/IP)은 www 접두사가 없어 영향 없음.
 */
class RedirectWww
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (str_starts_with($host, 'www.'))
        {
            $apex = substr($host, 4);
            $target = $request->getScheme() . '://' . $apex . $request->getRequestUri();

            return redirect($target, 301);
        }

        return $next($request);
    }
}
