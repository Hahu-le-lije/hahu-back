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

        // Use latest() which defaults to 'created_at'
        $recommendation = Recommendation::query()
            ->where('child_id', $childId)
            ->latest()
            ->first();

        if (!$recommendation) {
            return response()->json(['message' => 'No recommendations generated yet.'], 404);
        }

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
        $childInfo = $this->childService->getAuthenticatedChildProfile($childId);
        if (($childInfo['subscription_tier'] ?? 'Basic') === 'Basic') {
            return response()->json(['message' => 'Upgrade to view history.'], 403);
        }

        $history = Recommendation::query()
            ->where('child_id', $childId)
            ->select('created_at as generated_at', 'recommendation_text') // Alias it for the JSON response
            ->latest()
            ->limit(10)
            ->get();

        return response()->json(['history' => $history]);
    }

    public function getDashboardStatus(string $childId): JsonResponse
    {
        // Act as BFF (Backend for Frontend)
        $overview = $this->syncService->getAnalyticsOverview($childId);
        $snapshot = $this->syncService->getFeatureSnapshot($childId);

        $weekly = $overview['weekly_summary'] ?? null;

        if (!$weekly) {
            return response()->json(['message' => 'Not enough data.'], 404);
        }

        $healthScore = $weekly['mastery_score'] > 75 ? 'Excellent' : ($weekly['mastery_score'] > 50 ? 'Good' : 'Needs Focus');
        $consistency = ($snapshot['consistency_score'] ?? 0) > 0.8 ? 'Highly Consistent' : 'Inconsistent';

        return response()->json([
            'learning_health_score' => $healthScore,
            'time_spent_today_minutes' => round(($overview['daily_summary']['time_spent'] ?? 0) / 60),
            'weekly_accuracy' => $weekly['accuracy'],
            'consistency_status' => $consistency,
        ]);
    }
}