<?php

use App\Http\Controllers\MonitorLeadController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScanController::class, 'home']);
Route::get('/sitemap.xml', SitemapController::class);
Route::view('/how-it-works', 'pages.how-it-works');
Route::view('/non-custodial', 'pages.non-custodial');
Route::view('/api-docs', 'pages.api');
Route::post('/scan', [ScanController::class, 'store']);
Route::post('/monitor', [MonitorLeadController::class, 'store']);
// Public read-only JSON API lives in routes/api.php (sessionless): GET /api/scan/{address}

Route::get('/scan/{address}/og', [ScanController::class, 'og']);
Route::get('/scan/{address}/email', [ScanController::class, 'email']);
Route::get('/scan/{address}', [ScanController::class, 'show']);
