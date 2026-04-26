<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParentAuthController;
use App\Http\Controllers\ChildAuthController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ChildProfileController;
use App\Http\Controllers\ChildProgressController;

/*
|--------------------------------------------------------------------------
| Parent Auth
|--------------------------------------------------------------------------
*/
Route::prefix('parents')->group(function () {
    Route::post('/register', [ParentAuthController::class, 'register']);
    Route::post('/login', [ParentAuthController::class, 'login']);

    Route::middleware('auth:parent')->group(function () {
        Route::post('/logout', [ParentAuthController::class, 'logout']);
        Route::get('/me', [ParentAuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Child Auth
|--------------------------------------------------------------------------
*/
Route::prefix('children')->group(function () {
    Route::post('/login', [ChildAuthController::class, 'login']);

    Route::middleware('auth:child')->group(function () {
        Route::post('/logout', [ChildAuthController::class, 'logout']);
        Route::get('/me', [ChildAuthController::class, 'me']);
    });
});

/*
|--------------------------------------------------------------------------
| Parent → Children Management
|--------------------------------------------------------------------------
*/
Route::middleware('auth:parent')->group(function () {
    Route::apiResource('children', ChildController::class);
});

/*
|--------------------------------------------------------------------------
| Child Self Routes
|--------------------------------------------------------------------------
*/
Route::prefix('child')->middleware('auth:child')->group(function () {
    Route::get('/profile', [ChildProfileController::class, 'show']);
    Route::patch('/profile', [ChildProfileController::class, 'update']);

    Route::get('/progress', [ChildProgressController::class, 'index']);
    Route::post('/progress', [ChildProgressController::class, 'store']);
});
