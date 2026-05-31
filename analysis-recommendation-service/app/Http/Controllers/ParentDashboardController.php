<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use App\Services\ChildServiceClient;
use App\Services\SyncServiceClient;
use App\Services\UserServiceClient;
use Illuminate\Http\JsonResponse;

class ParentDashboardController extends Controller
{
    protected SyncServiceClient $syncService;
    protected ChildServiceClient $childService;

    public function __construct(SyncServiceClient $syncService, ChildServiceClient $childService)
    {
        $this->syncService = $syncService;
        $this->childService = $childService;
    }

    public function getLatestRecommendation(string $childId): JsonResponse
    {
        error_log("ParentDashboardController@getLatestRecommendation - Start - Child: {$childId}");

        // Use latest() which defaults to 'created_at'
        $recommendation = Recommendation::query()
            ->where('child_id', $childId)
            ->latest()
            ->first();

        if (!$recommendation) {
            error_log("ParentDashboardController@getLatestRecommendation - Not found - Child: {$childId}");
            return response()->json(['message' => 'No recommendations generated yet.'], 404);
        }

        error_log("ParentDashboardController@getLatestRecommendation - Success - Child: {$childId}");
        return response()->json([
            'child_id' => $childId,
            'tier' => $recommendation->tier,
            'generated_at' => $recommendation->created_at, // Map created_at to generated_at for the frontend
            'recommendation_text' => $recommendation->recommendation_text,
            'next_update_expected_at' => $recommendation->next_update_expected_at
        ]);
    }

    public function getRecommendationHistory(string $childId): JsonResponse
    {
        error_log("ParentDashboardController@getRecommendationHistory - Start - Child: {$childId}");
        
        $childInfo = $this->childService->getAuthenticatedChildProfile($childId);
        if (($childInfo['subscription_tier'] ?? 'basic') === 'basic') {
            error_log("ParentDashboardController@getRecommendationHistory - Access denied (basic tier) - Child: {$childId}");
            return response()->json(['message' => 'Upgrade to view history.'], 403);
        }

        $history = Recommendation::query()
            ->where('child_id', $childId)
            ->select('created_at as generated_at', 'recommendation_text') // Alias it for the JSON response
            ->latest()
            ->limit(10)
            ->get();

        error_log("ParentDashboardController@getRecommendationHistory - Success - Found " . $history->count() . " records - Child: {$childId}");
        return response()->json(['history' => $history]);
    }

    public function getDashboardStatus(string $childId): JsonResponse
    {
        error_log("ParentDashboardController@getDashboardStatus - Start - Child: {$childId}");
        // Act as BFF (Backend for Frontend)
        $overview = $this->syncService->getAnalyticsOverview($childId);
        $snapshot = $this->syncService->getFeatureSnapshot($childId);

        $weekly = $overview['weekly_summary'] ?? null;

        if (!$weekly) {
            error_log("ParentDashboardController@getDashboardStatus - Not enough data - Child: {$childId}");
            return response()->json(['message' => 'Not enough data.'], 404);
        }

        $healthScore = $weekly['mastery_score'] > 75 ? 'Excellent' : ($weekly['mastery_score'] > 50 ? 'Good' : 'Needs Focus');
        $consistency = ($snapshot['consistency_score'] ?? 0) > 0.8 ? 'Highly Consistent' : 'Inconsistent';

        error_log("ParentDashboardController@getDashboardStatus - Success - Child: {$childId}");
        return response()->json([
            'learning_health_score' => $healthScore,
            'time_spent_today_minutes' => round(($overview['daily_summary']['time_spent'] ?? 0) / 60),
            'weekly_accuracy' => $weekly['accuracy'],
            'consistency_status' => $consistency,
        ]);
    }
}
