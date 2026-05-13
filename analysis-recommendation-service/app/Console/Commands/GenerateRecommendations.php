<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recommendation;
use App\Services\SyncServiceClient;
use App\Services\AiRecommendationService;
use App\Services\UserServiceClient;
use Carbon\Carbon;

class GenerateRecommendations extends Command
{
    protected $signature = 'app:generate-recommendations';
    protected $description = 'Generate AI recommendations based on subscription tiers';

    public function handle(SyncServiceClient $sync, AiRecommendationService $ai, UserServiceClient $userService)
    {
        $children = $userService->getAllActiveChildren();
        $now = Carbon::now('Africa/Addis_Ababa');

        foreach ($children as $child) {
            $childId = $child['id'];
            $childName = $child['name'];
            $tier = $child['subscription_tier'] ?? 'Basic';

            // Use latest() to get the newest by created_at
            $latestRecommendation = Recommendation::query()
                ->where('child_id', $childId)
                ->latest()
                ->first();

            $lastUpdate = $latestRecommendation?->created_at; // Use created_at instead

            $needsUpdate = false;
            $nextUpdateExpected = null;

            if ($tier === 'Ultimate' && (!$lastUpdate || $lastUpdate->diffInDays($now) >= 1)) {
                $needsUpdate = true;
                $nextUpdateExpected = $now->copy()->addDay();
            } elseif ($tier === 'Premium' && (!$lastUpdate || $lastUpdate->diffInDays($now) >= 3)) {
                $needsUpdate = true;
                $nextUpdateExpected = $now->copy()->addDays(3);
            } elseif ($tier === 'Basic' && (!$lastUpdate || $lastUpdate->diffInDays($now) >= 14)) {
                $needsUpdate = true;
                $nextUpdateExpected = $now->copy()->addDays(14);
            }

            if (!$needsUpdate)
                continue;

            $overview = $sync->getAnalyticsOverview($childId);

            if ($tier === 'Ultimate' && empty($overview['daily_summary'])) {
                $this->saveRecommendation($childId, $tier, "No activity today! We'll be ready for {$childName} when they log back in.", $nextUpdateExpected);
                continue;
            }

            $snapshot = $sync->getFeatureSnapshot($childId);
            $sinceDate = $lastUpdate ? $lastUpdate->toIso8601String() : $now->copy()->subDays(14)->toIso8601String();
            $events = $sync->getRecentEvents($childId, $sinceDate);

            $data = [
                'daily' => json_encode($overview['daily_summary'] ?? []),
                'weekly' => json_encode($overview['weekly_summary'] ?? []),
                'snapshot' => json_encode($snapshot ?? []),
                'events' => json_encode($events['events'] ?? []),
                'child_name' => $childName
            ];

            $recommendationText = $ai->generateRecommendation($tier, $data);
            $this->saveRecommendation($childId, $tier, $recommendationText, $nextUpdateExpected);
        }

        $this->info('Recommendations generated successfully.');
    }

    // Removed $now parameter since created_at handles it automatically
    private function saveRecommendation(string $childId, string $tier, string $text, Carbon $next)
    {
        Recommendation::create([
            'child_id' => $childId,
            'tier' => $tier,
            'recommendation_text' => $text,
            'next_update_expected_at' => $next,
        ]);
    }
}