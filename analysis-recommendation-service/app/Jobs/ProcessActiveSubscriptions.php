<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\RabbitRpcClient;
use App\Services\AiRecommendationService;
use App\Models\Recommendation;
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
    }

    // Laravel automatically injects these services from the ARS container
    public function handle(RabbitRpcClient $rpcClient, AiRecommendationService $ai)
    {
        foreach ($this->subscriptions as $sub) {
            $subscriptionId = $sub['subscription_id'];
            $tier = $sub['tier'];

            try {
                // 1. RPC Call to CS to get children for this subscription
                $csResponse = $rpcClient->call('cs_get_children_queue', ['subscription_id' => $subscriptionId]);
                $childIds = $csResponse['child_ids'] ??[];

                foreach ($childIds as $childId) {
                    // 2. RPC Call to SyncS to get analytics
                    $syncData = $rpcClient->call('syncs_get_analytics_queue', ['child_id' => $childId]);

                    // Skip Gemini logic for Ultimate users with no daily activity to save API costs
                    if ($tier === 'Ultimate' && empty($syncData['daily_summary'])) {
                        $this->saveRecommendation($childId, $tier, "No activity today! We'll be ready when they log back in.");
                        continue;
                    }

                    // 3. Call Gemini using Laravel/AI wrapper
                    $recommendationText = $ai->generateRecommendation($tier, $syncData);

                    // 4. Save to Database
                    $this->saveRecommendation($childId, $tier, $recommendationText);
                }

            } catch (Exception $e) {
                // Log and continue so one failing subscription doesn't crash the whole batch
                Log::error("Failed processing subscription {$subscriptionId}: " . $e->getMessage());
                continue; 
            }
        }
    }

    private function saveRecommendation(string $childId, string $tier, string $text)
    {
        $now = Carbon::now('Africa/Addis_Ababa');
        
        // Calculate when the next update should happen based on the tier
        $next = match ($tier) {
            'Ultimate' => $now->copy()->addDay(),
            'Premium' => $now->copy()->addDays(3),
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