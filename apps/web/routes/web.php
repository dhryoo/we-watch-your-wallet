<?php

use App\Http\Controllers\MonitorLeadController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScanController::class, 'home']);
Route::view('/how-it-works', 'pages.how-it-works');
Route::view('/non-custodial', 'pages.non-custodial');
Route::post('/scan', [ScanController::class, 'store']);
Route::post('/monitor', [MonitorLeadController::class, 'store']);
// Public read-only JSON API lives in routes/api.php (sessionless): GET /api/scan/{address}

Route::get('/scan/{address}/og', [ScanController::class, 'og']);
Route::get('/scan/{address}/email', [ScanController::class, 'email']);
Route::get('/scan/{address}', [ScanController::class, 'show']);
