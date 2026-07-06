<?php

use App\Http\Middleware\RedirectWww;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',   // sessionless/cookieless group for the public JSON API
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Canonical host: 301 www.* → apex on every request (SEO — one indexable host, no duplicates).
        $middleware->prepend(RedirectWww::class);

        // Real client IP (which the per-IP rate limits depend on) is correct out of the box with
        // direct nginx → PHP-FPM. If a CDN/LB (e.g. Cloudflare) is later put in front, fix the IP
        // at the nginx layer with the realip module (see deploy/nginx/we-watch.conf) rather than
        // trusting X-Forwarded-* here — that avoids spoofable headers entirely.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
