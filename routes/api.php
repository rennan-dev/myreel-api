<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MediaController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// tracking público (chamado pelo frontend) com rate limit
Route::post('/analytics/track', [AnalyticsController::class, 'track'])
    ->middleware('throttle:60,1');

// dados do dashboard (protegidos por chave compartilhada)
Route::middleware('analytics.key')->group(function () {
    Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/media/{media}', [MediaController::class, 'show']);
    Route::put('/media/{media}', [MediaController::class, 'update']);
    Route::patch('/media/{media}', [MediaController::class, 'update']);
    Route::delete('/media/{media}', [MediaController::class, 'destroy']);

    Route::get('/me', function () {
        return auth()->user();
    });
});
