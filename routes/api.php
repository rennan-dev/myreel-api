<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MediaController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/media', [MediaController::class, 'index']);
    Route::post('/media', [MediaController::class, 'store']);
    Route::get('/media/{media}', [MediaController::class, 'show']);
    Route::post('/media/{media}/seasons', [MediaController::class, 'storeSeason']);
    Route::patch('/seasons/{season}', [MediaController::class, 'updateSeason']);
    Route::post('/seasons/{season}/episodes', [MediaController::class, 'storeEpisode']);
    Route::patch('/episodes/{episode}', [MediaController::class, 'updateEpisode']);

    Route::get('/me', function() {
        return auth()->user();
    });
});