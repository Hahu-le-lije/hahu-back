<?php

use App\Http\Controllers\ChildAuthController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ChildProfileController;
use App\Http\Controllers\SubscriptionAssignmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Child Auth
|--------------------------------------------------------------------------
*/
Route::prefix('children')->group(function () {
    Route::post('/login', [ChildAuthController::class, 'login']);

    Route::middleware('jwt.child')->group(function () {
        Route::post('/logout', [ChildAuthController::class, 'logout']);
        Route::get('/me', [ChildProfileController::class, 'show']);
    });
});

/*
|--------------------------------------------------------------------------
| Parent-owned Child Account Management
|--------------------------------------------------------------------------
*/
Route::prefix('parents')->middleware('clerk.parent')->group(function () {
    Route::apiResource('children', ChildController::class);
    Route::post('/children/{child}/credentials', [ChildController::class, 'resetCredentials']);
});

/*
|--------------------------------------------------------------------------
| Internal Subscription Service Integration
|--------------------------------------------------------------------------
*/
Route::prefix('internal/subscriptions')
    ->middleware('subscription.service')
    ->group(function () {
        Route::post('/assign-child', [SubscriptionAssignmentController::class, 'store']);
    });
