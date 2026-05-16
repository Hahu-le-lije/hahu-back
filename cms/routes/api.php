<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentPackController;
use App\Http\Controllers\ContentPackVersionController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Route;

Route::prefix('parents')->group(function () {
    Route::post('sign-up', [ParentController::class, 'signUp']);

    Route::middleware('clerk.auth')->group(function () {
        Route::get('me', [ParentController::class, 'me']);
        Route::put('update', [ParentController::class, 'update']);
        Route::delete('delete', [ParentController::class, 'delete']);
    });
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
    });
});

Route::prefix('content')->group(function () {
    Route::get('packs', [ContentPackController::class, 'index'])->name('content.packs.index');
    Route::get('packs/{slug}/manifest', [ContentPackController::class, 'manifest'])->name('content.packs.manifest');
    Route::get('packs/{slug}/download', [ContentPackController::class, 'download'])->name('content.packs.download');
});

Route::prefix('subjects')->group(function () {
    Route::get('/', [SubjectController::class, 'index']);
    Route::put('/', [SubjectController::class, 'update']);
});

// Task recommendations and assignment endpoints
Route::prefix('children')->group(function () {
    Route::get('{child_id}/tasks/recommendations', [\App\Http\Controllers\TaskRecommendationController::class, 'recommendations']);
    Route::post('{child_id}/tasks/assign', [\App\Http\Controllers\TaskRecommendationController::class, 'assign']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('content-packs', [ContentPackController::class, 'adminIndex']);
    Route::get('content-packs/{id}', [ContentPackController::class, 'show']);
    Route::post('content-packs', [ContentPackController::class, 'store']);
    Route::put('content-packs/{id}', [ContentPackController::class, 'update']);
    Route::delete('content-packs/{id}', [ContentPackController::class, 'destroy']);

    Route::get('content-pack-versions', [ContentPackVersionController::class, 'index']);
    Route::post('content-pack-versions', [ContentPackVersionController::class, 'store']);
    Route::put('content-pack-versions/{id}', [ContentPackVersionController::class, 'update']);
    Route::delete('content-pack-versions/{id}', [ContentPackVersionController::class, 'destroy']);
});
