<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SubscriptionServiceClient
{
    protected string $baseUrl;
    protected string $secretToken;

    public function __construct()
    {
        $this->baseUrl = config('services.subscription_service.url', 'http://localhost:8003');
        $this->secretToken = config('services.subscription_service.secret_token', '');
    }

    /**
     * Get subscription details for a parent
     * 
     * @param string $parentId
     * @return array|null Array of subscriptions or null if none found
     */
    public function getParentSubscriptions(string $parentId): ?array
    {
        try {
            $response = Http::withToken($this->secretToken)
                ->timeout(5)
                ->get("{$this->baseUrl}/api/internal/subscriptions/parent/{$parentId}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return $data['data'] ?? [];
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Subscription service unavailable for parent {$parentId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if parent has an active subscription
     * 
     * @param string $parentId
     * @return bool
     */
    public function hasActiveSubscription(string $parentId): bool
    {
        $subscriptions = $this->getParentSubscriptions($parentId);
        
        if (!$subscriptions || !is_array($subscriptions)) {
            return false;
        }

        foreach ($subscriptions as $subscription) {
            if (isset($subscription['status']) && $subscription['status'] === 'active') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get subscription tier for a parent (basic, premium, ultimate)
     * 
     * @param string $parentId
     * @return string|null
     */
    public function getSubscriptionTier(string $parentId): ?string
    {
        $subscriptions = $this->getParentSubscriptions($parentId);
        
        if (!$subscriptions || !is_array($subscriptions)) {
            return null;
        }

        // Get the highest tier subscription
        $tiers = ['ultimate' => 3, 'premium' => 2, 'basic' => 1];
        $highestTier = null;
        $highestScore = 0;

        foreach ($subscriptions as $subscription) {
            if (isset($subscription['status']) && $subscription['status'] === 'active') {
                $tier = strtolower($subscription['tier'] ?? 'basic');
                $score = $tiers[$tier] ?? 0;
                if ($score > $highestScore) {
                    $highestScore = $score;
                    $highestTier = $tier;
                }
            }
        }

        return $highestTier;
    }

    /**
     * Verify child is linked to parent's subscription
     * 
     * @param string $childId
     * @param string $parentId
     * @return bool
     */
    public function isChildLinkedToSubscription(string $childId, string $parentId): bool
    {
        try {
            $response = Http::withToken($this->secretToken)
                ->timeout(5)
                ->get("{$this->baseUrl}/api/internal/subscriptions/verify-child", [
                    'child_id' => $childId,
                    'parent_id' => $parentId,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['status']) && $data['status'] === 'success' && ($data['linked'] ?? false);
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning("Failed to verify child subscription link: " . $e->getMessage());
            return false;
        }
    }
}
