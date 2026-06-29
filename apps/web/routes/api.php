<?php

use App\Http\Controllers\Api\ScanApiController;
use Illuminate\Support\Facades\Route;

// Sessionless/cookieless public JSON API (the `api` group has no StartSession/cookies/CSRF).
// → GET /api/scan/{address} (ethereum) and GET /api/scan/{chain}/{address}.
// Chain is validated in the controller (422 unsupported_chain) rather than constrained here.
Route::get('/scan/{chain}/{address}', [ScanApiController::class, 'show']);
Route::get('/scan/{address}', [ScanApiController::class, 'show']);
