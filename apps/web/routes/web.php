<?php

use App\Http\Controllers\MonitorLeadController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SitemapController;
use App\Scan\Chain;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScanController::class, 'home']);
Route::get('/sitemap.xml', SitemapController::class);
Route::view('/how-it-works', 'pages.how-it-works');
Route::view('/non-custodial', 'pages.non-custodial');
Route::view('/api-docs', 'pages.api');
Route::post('/scan', [ScanController::class, 'store']);
Route::post('/monitor', [MonitorLeadController::class, 'store']);
// Public read-only JSON API lives in routes/api.php (sessionless): GET /api/scan/{address}

// Multichain: /scan/{chain}/{address} (chain ∈ supported slugs). The bare /scan/{address}
// stays for back-compat and defaults to ethereum.
// PNG share card path has no .png/.jpg/... extension so nginx's static-asset handler doesn't
// intercept it (it would 404 before reaching PHP); crawlers use Content-Type.
Route::get('/scan/{chain}/{address}/og-image', [ScanController::class, 'ogImage'])->whereIn('chain', Chain::slugs());
Route::get('/scan/{chain}/{address}/og', [ScanController::class, 'og'])->whereIn('chain', Chain::slugs());
Route::get('/scan/{chain}/{address}', [ScanController::class, 'show'])->whereIn('chain', Chain::slugs());

Route::get('/scan/{address}/og-image', [ScanController::class, 'ogImage'])->defaults('chain', Chain::DEFAULT);
Route::get('/scan/{address}/og', [ScanController::class, 'og'])->defaults('chain', Chain::DEFAULT);
Route::get('/scan/{address}/email', [ScanController::class, 'email']);
Route::get('/scan/{address}', [ScanController::class, 'show'])->defaults('chain', Chain::DEFAULT);
