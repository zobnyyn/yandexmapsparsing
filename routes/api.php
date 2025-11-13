<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\YandexController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/yandex/setting', [YandexController::class, 'saveSetting']);
    Route::get('/yandex/setting', [YandexController::class, 'getSetting']);
    Route::post('/yandex/fetch', [YandexController::class, 'fetchReviews']);
    Route::get('/yandex/cached', [YandexController::class, 'getCachedData']);
});

