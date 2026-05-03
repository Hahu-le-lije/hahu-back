<?php

use App\Http\Controllers\ParentController;
use Illuminate\Support\Facades\Route;

Route::prefix('parents')->group(function () {
    Route::post('sign-up', [ParentController::class, 'signUp']);

    Route::middleware('clerk.auth')->group(function () {
        Route::get('me', [ParentController::class, 'me']);
        Route::put('update', [ParentController::class, 'update']);
        Route::delete('delete', [ParentController::class, 'delete']);
    });
});

use App\Http\Controllers\ContentPackController;

Route::prefix('content')->group(function () {
    Route::get('packs', [ContentPackController::class, 'index'])->name('content.packs.index');
    Route::get('packs/{slug}/manifest', [ContentPackController::class, 'manifest'])->name('content.packs.manifest');
    Route::get('packs/{slug}/download', [ContentPackController::class, 'download'])->name('content.packs.download');
});
