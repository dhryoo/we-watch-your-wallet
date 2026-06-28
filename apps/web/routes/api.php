<?php

use App\Http\Controllers\Api\ScanApiController;
use Illuminate\Support\Facades\Route;

// Sessionless/cookieless public JSON API (the `api` group has no StartSession/cookies/CSRF).
// → GET /api/scan/{address}
Route::get('/scan/{address}', [ScanApiController::class, 'show']);
