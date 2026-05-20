<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class InternalUserService
{
    protected string $baseParentServiceUrl;
    protected string $baseChildServiceUrl;
    protected string $authToken;

    public function __construct()
    {
        error_log("InternalUserService::__construct - Initializing service.");
        $this->baseParentServiceUrl = config('services.parent_service.url');
        $this->baseChildServiceUrl = config('services.child_service.url');
        $this->authToken = env('INTERNAL_SERVICE_TOKEN', '');
        error_log("InternalUserService::__construct - Parent URL: {$this->baseParentServiceUrl}, Child URL: {$this->baseChildServiceUrl}");
    }

    public function getParent(string $userId): ?array
    {
        error_log("InternalUserService::getParent - Method started for User ID: {$userId}");
        $secretKey = config('services.clerk.secret_key');

        error_log("InternalUserService::getParent - Making HTTP GET request to Clerk API.");
        // Make an HTTP GET request to the specific user endpoint
        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->get("https://api.clerk.com/v1/users/{$userId}");

        error_log("InternalUserService::getParent - Clerk API response status: " . $response->status());

        // Clerk returns a 404 status code if the user_id does not exist
        // Handle other potential API errors (e.g., 401 Unauthorized, 500 Server Error)
        if ($response->failed() || $response->status() === 404) {
            error_log("InternalUserService::getParent - Clerk API Error detected. Throwing exception. Body: " . $response->body());
            throw new Exception("Clerk API Error: " . $response->body());
        }

        error_log("InternalUserService::getParent - Successfully fetched user from Clerk. Parsing response.");
        // The user exists! Return the parsed JSON data representing the user.
        // (Alternatively, return true here if you only want a boolean check)
        // and convert it to a minimal array with only the necessary fields for the parent service
        $mini_response = [
            'id' => $response->json('id'),
            'email' => $response->json('email_addresses')[0]['email_address'] ?? null,
            'first_name' => $response->json('first_name'),
            'last_name' => $response->json('last_name'),
        ];
        
        error_log("InternalUserService::getParent - Returning mini_response array for User ID: {$userId}");
        return $mini_response;
    }

    public function getChild(string $childId)
    {
        error_log("InternalUserService::getChild - Method started for Child ID: {$childId}");
        
        error_log("InternalUserService::getChild - Making HTTP GET request to internal child service.");
        $response = Http::withToken($this->authToken)->get("{$this->baseChildServiceUrl}/api/internal/get-child/{$childId}");
        
        error_log("InternalUserService::getChild - Child service response status: " . $response->status());

        if ($response->failed() || $response->json('status') !== 'success' || !isset($response->json()['data'])) {
            error_log("InternalUserService::getChild - Failed to fetch child data. Throwing exception. Body: " . $response->body());
            throw new Exception("Failed to fetch child data: " . $response->body());
        }
        
        error_log("InternalUserService::getChild - Successfully fetched child data. Returning data array.");
        return $response->json()['data'];
    }
}