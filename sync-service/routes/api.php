<?php

use App\Http\Controllers\Api\AnalyticsOverviewController;
use App\Http\Controllers\Api\DailySummaryController;
use App\Http\Controllers\Api\WeeklySummaryController;
use App\Http\Controllers\Api\AiExportController;

Route::get(
    '/children/{childId}/daily-summary',
    [DailySummaryController::class, 'show']
);

Route::get(
    '/children/{childId}/weekly-summary',
    [WeeklySummaryController::class, 'show']
);

Route::get(
    '/children/{childId}/analytics-overview',
    [AnalyticsOverviewController::class, 'show']
);

// Old route below, might use for testing later
/*
Route::get(
    '/ai/children/{childId}/feature-snapshot',
    [AiExportController::class, 'featureSnapshot']
);
*/

Route::get(
    '/ai/children/{childId}/events',
    [AiExportController::class, 'exportEvents']
);

Route::middleware('service.jwt')->group(function () {

    Route::get(
        '/ai/children/{childId}/feature-snapshot',
        [AiExportController::class, 'featureSnapshot']
    );

});
