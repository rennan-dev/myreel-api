<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/media/{media}/seasons', [App\Http\Controllers\Api\MediaController::class, 'storeSeason']);
    Route::post('/seasons/{season}/episodes', [App\Http\Controllers\Api\MediaController::class, 'storeEpisode']);
    Route::get('/media', [App\Http\Controllers\Api\MediaController::class, 'index']);
    Route::post('/media', [App\Http\Controllers\Api\MediaController::class, 'store']);

    Route::get('/me', function() {
        return auth()->user();
    });
});