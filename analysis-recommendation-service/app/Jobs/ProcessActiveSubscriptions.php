<?php

namespace App\Jobs;

use Date;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SyncServiceClient;
use App\Services\AiRecommendationService;
use App\Models\Recommendation;
use App\Services\ChildServiceClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class ProcessActiveSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public array $subscriptions;

    public function __construct(array $subscriptions)
    {
        $this->subscriptions = $subscriptions;
        $this->queue = 'ars_subscriptions_queue';

    }

    // Laravel automatically injects these services from the ARS container
    public function handle(SyncServiceClient $sync, ChildServiceClient $childService, AiRecommendationService $ai)
    {
        foreach ($this->subscriptions as $sub) {
            $subscriptionId = $sub['subscription_id'];
            $tier = $sub['tier'];
            $now = Carbon::now('Africa/Addis_Ababa');
            try {

                $csResponse = $childService->getChildWithSubscription($subscriptionId);


                foreach ($csResponse as $child) {
                    $childId = $child['child_id'];
                    $childName = $child['child_name'];
                    [$lastUpdate, $needsUpdate] = $this->recommendationNeeded($tier, $childId);
                    if (!$needsUpdate) {
                        continue;
                    }

                    $overview = $sync->getAnalyticsOverview($childId);

                    if ($tier === 'Ultimate' && empty($overview['daily_summary'])) {
                        $this->saveRecommendation($childId, $tier, "No activity today! We'll be ready for {$childName} when they log back in.");
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
                    $this->saveRecommendation($childId, $tier, $recommendationText);
                }

            } catch (Exception $e) {
                // Log and continue so one failing subscription doesn't crash the whole batch
                Log::error("Failed processing subscription {$subscriptionId}: " . $e->getMessage());
                continue;
            }
        }
    }

    private function recommendationNeeded(string $tier, string $childId): array
    {
        $now = Carbon::now('Africa/Addis_Ababa');
        // Use latest() to get the newest by created_at
        $latestRecommendation = Recommendation::query()
            ->where('child_id', $childId)
            ->latest()
            ->first();

        $lastUpdate = $latestRecommendation?->created_at; // Use created_at instead


        if ($tier === 'Ultimate' && (!$lastUpdate || $lastUpdate->diffInDays($now) >= 1)) {
            return [$lastUpdate, true];
        } elseif ($tier === 'Premium' && (!$lastUpdate || $lastUpdate->diffInDays($now) >= 3)) {
            return [$lastUpdate, true];
        } elseif ($tier === 'Basic' && (!$lastUpdate || $lastUpdate->diffInDays($now) >= 14)) {
            return [$lastUpdate, true];
        }

        return [null, false];
    }

    private function saveRecommendation(string $childId, string $tier, string $text)
    {
        $now = Carbon::now('Africa/Addis_Ababa');

        // Calculate when the next update should happen based on the tier
        //! if match is Case sensetive it might cause issues.
        $next = match (strtolower($tier)) {
            'ultimate' => $now->copy()->addDay(), // should be case-insens
            'premium' => $now->copy()->addDays(3),
            default => $now->copy()->addDays(14), // Basic
        };
        
        Recommendation::create([
            'child_id' => $childId,
            'tier' => $tier,
            'recommendation_text' => $text,
            'next_update_expected_at' => $next,
        ]);
    }
}