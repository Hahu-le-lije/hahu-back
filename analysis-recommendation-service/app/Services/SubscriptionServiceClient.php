<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SubscriptionServiceClient
{
    protected string $baseUrl;
    protected string $subsServiceToken;

    public function __construct()
    {
        
        $this->baseUrl = config('services.subscription_service.url', 'http://subscription-service');
        $this->subsServiceToken = config('services.subscription_service.secret_token');
    }
    public function getSubscriptionDetails(string $subscriptionId): ?array
    {
        error_log("SubscriptionServiceClient@getSubscriptionDetails - Fetching subscription: {$subscriptionId}");
        $response = Http::withToken($this->subsServiceToken)
            ->get("{$this->baseUrl}/api/internal/subscriptions/{$subscriptionId}");

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
                error_log("SubscriptionServiceClient@getSubscriptionDetails - Success - Subscription: {$subscriptionId}");
                return $data['data']; // Return the subscription details
            }
            error_log("SubscriptionServiceClient@getSubscriptionDetails - Invalid Response Format - Subscription: {$subscriptionId}");
        }

        error_log("SubscriptionServiceClient@getSubscriptionDetails - Failed - Status: {$response->status()} - Subscription: {$subscriptionId}");
        throw new \Exception("Failed to fetch subscription details for subscription ID: {$subscriptionId}. Status: {$response->status()}");

        return null; // Token is missing, invalid, or subscription is unavailable
    }

}
