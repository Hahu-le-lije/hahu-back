<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChildServiceClient
{
    protected string $baseUrl;
    protected string $childServiceToken;

    public function __construct()
    {
        // Define this URL in your config/services.php or .env file
        $this->baseUrl = config('services.child_service.url', 'http://child-service');
        // In reality, fetch/generate this JWT securely via your auth mechanism
        $this->childServiceToken = config('services.child_service.secret_token');
    }

    /**
     * Gets the authenticated child profile using the forwarded Bearer token.
     * Endpoint: GET /api/server/children/{childId}
     * the structure is going to be like this:
     * {
     *  "status": "success",
     *   "data": {
     *     "id": "123",
     *    "parent_id": "parent_id_123"
     *   }
     * }
     */
    public function getAuthenticatedChildProfile(string $childId): ?array
    {
        error_log("ChildServiceClient@getAuthenticatedChildProfile - Fetching child: {$childId}");
        $response = Http::withToken($this->childServiceToken)
            ->get("{$this->baseUrl}/api/internal/children/{$childId}");

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
                error_log("ChildServiceClient@getAuthenticatedChildProfile - Success - Child: {$childId}");
                return $data['data']; // Return the child profile
            }
            error_log("ChildServiceClient@getAuthenticatedChildProfile - Invalid Response Format - Child: {$childId}");
        }

        error_log("ChildServiceClient@getAuthenticatedChildProfile - Failed - Status: {$response->status()} - Child: {$childId}");
        throw new \Exception("Failed to fetch child profile for child ID: {$childId}. Status: {$response->status()}");

        return null; // Token is missing, invalid, or child is unavailable
    }

    /**
     * Summary of getChildWithSubscription
     * @param string $subscriptionId
     * @return array|null Returns child details along with subscription info, or null if not found
     * the structure is going to be like this:
     *{
     *    "status": "success",
     *     "data": [
     *       {
     *         "child_id": "123",
     *         "child_name": "John Doe"
     *       }
     *     ]
     *   }
     */
    public function getChildWithSubscription(string $subscriptionId): ?array
    {
        error_log("ChildServiceClient@getChildWithSubscription - Fetching subscription: {$subscriptionId}");
        $response = Http::withToken($this->childServiceToken)
            ->get("{$this->baseUrl}/api/internal/subscriptions/children/{$subscriptionId}");

        if ($response->successful()) {
            $data = $response->json();
            if (isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
                error_log("ChildServiceClient@getChildWithSubscription - Success - Subscription: {$subscriptionId}");
                return $data['data']; // Return the array of child details
            }
            error_log("ChildServiceClient@getChildWithSubscription - Invalid Response Format - Subscription: {$subscriptionId}");
        }

        error_log("ChildServiceClient@getChildWithSubscription - Failed - Status: {$response->status()} - Subscription: {$subscriptionId}");
        throw new \Exception("Failed to fetch child with subscription ID: {$subscriptionId}. Status: {$response->status()}");
    }
}
