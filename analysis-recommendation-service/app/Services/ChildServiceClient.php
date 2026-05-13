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
     */
    public function getAuthenticatedChildProfile(string $childId): ?array
    {
        $response = Http::withToken($this->childServiceToken)->get("{$this->baseUrl}/api/server/children/{$childId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null; // Token is missing, invalid, or child is unavailable
    }

    /**
     * Summary of getChildWithSubscription
     * @param string $subscriptionId
     * @return array|null Returns child details along with subscription info, or null if not found
     * the structure is going to be like this:
     * [
     *     [
     *      'child_id' => '123',
     *      'child_name' => 'John Doe',
     *     ],
     *     [
     *      'child_id' => '123',
     *      'child_name' => 'John Doe',
     *     ],
     * ]
     */
    public function getChildWithSubscription(string $subscriptionId): ?array
    {
        $response = Http::get("{$this->baseUrl}/api/server/subscriptions/children/{$subscriptionId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null; // Child not found or service error
    }
}