<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Middleware\GuardianVerifyAuth;
use App\Http\Middleware\ClerkAuthMiddleware;

// Protect these with your standard parent auth middleware
// alias is showing minor error in the IDE but it works fine when running the app, so ignoring it for now.
Route::middleware([ClerkAuthMiddleware::class, GuardianVerifyAuth::class])->prefix('parents/children/{childId}')->group(function () {
    
    // Gets the instantly available AI text
    Route::get('/recommendation', [ParentDashboardController::class, 'getLatestRecommendation']);
    
    // Gets the analytical dashboard numbers
    Route::get('/dashboard-status',[ParentDashboardController::class, 'getDashboardStatus']);
    
    // Gets past recommendations
    Route::get('/recommendation/history',[ParentDashboardController::class, 'getRecommendationHistory']);

});