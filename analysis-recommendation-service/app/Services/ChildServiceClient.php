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
     * Endpoint: GET /api/parents/children/{childId}
     */
    public function getAuthenticatedChildProfile(string $token, string $childId): ?array
    {
        $response = Http::withToken($token)->get("{$this->baseUrl}/api/parents/children/{$childId}");
        
        if ($response->successful()) {
            return $response->json();
        }

        return null; // Token is missing, invalid, or child is unavailable
    }
}