<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChildServiceClient
{
    protected string $baseUrl;

    public function __construct()
    {
        // Define this URL in your config/services.php or .env file
        $this->baseUrl = config('services.child_service.url', 'http://child-service');
    }

    /**
     * Gets the authenticated child profile using the forwarded Bearer token.
     * Endpoint: GET /api/server/children/{childId}
     */
    public function getAuthenticatedChildProfile(string $token, string $childId): ?array
    {
        $response = Http::withToken($token)->get("{$this->baseUrl}/api/server/children/{$childId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null; // Token is missing, invalid, or child is unavailable
    }

    /**
     * Summary of getChildWithSubscription
     * @param string $childId
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
    public function getChildWithSubscription(string $childId): ?array
    {
        $response = Http::get("{$this->baseUrl}/api/server/subscriptions/children/{$childId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null; // Child not found or service error
    }
}