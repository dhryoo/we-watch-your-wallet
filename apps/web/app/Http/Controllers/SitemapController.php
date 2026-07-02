<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * sitemap.xml — only the public, indexable pages. Per-wallet /scan/* pages are robots-disallowed
 * (ephemeral, point-in-time, scan-budget) so they are intentionally NOT listed.
 * Invokable controller (not a closure) so the route survives `php artisan route:cache`.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $paths = [
            '/', '/how-it-works', '/non-custodial', '/api-docs',
            '/learn',
            '/learn/what-is-a-token-approval',
            '/learn/how-to-revoke-token-approvals',
            '/learn/unlimited-token-approvals',
            '/learn/approval-phishing',
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($paths as $path)
        {
            $xml .= '  <url><loc>' . e(url($path)) . '</loc></url>' . "\n";
        }

        $xml .= '</urlset>' . "\n";

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
