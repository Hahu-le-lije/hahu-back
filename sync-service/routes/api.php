<?php

use App\Http\Controllers\Api\AnalyticsOverviewController;
use App\Http\Controllers\Api\DailySummaryController;
use App\Http\Controllers\Api\LearningSessionController;
use App\Http\Controllers\Api\LatestSummariesController;
use App\Http\Controllers\Api\WeeklySummaryController;
use App\Http\Controllers\Api\AiExportController;

Route::post(
    '/sessions',
    [LearningSessionController::class, 'store']
);

Route::post(
    '/learning-events',
    [LearningSessionController::class, 'store']
);

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

Route::get(
    '/children/{childId}/summaries/latest',
    [LatestSummariesController::class, 'show']
);

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
