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
        $this->baseParentServiceUrl = config('services.parent_service.url');
        $this->baseChildServiceUrl = config('services.child_service.url');
        $this->authToken = env('INTERNAL_SERVICE_TOKEN', '');
    }

    public function getParent(string $userId): ?array
    {
        $secretKey = config('services.clerk.secret_key');

        // Make an HTTP GET request to the specific user endpoint
        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->get("https://api.clerk.com/v1/users/{$userId}");

        // Clerk returns a 404 status code if the user_id does not exist
        // Handle other potential API errors (e.g., 401 Unauthorized, 500 Server Error)
        if ($response->failed() || $response->status() === 404) {
            throw new Exception("Clerk API Error: " . $response->body());
        }

        // The user exists! Return the parsed JSON data representing the user.
        // (Alternatively, return true here if you only want a boolean check)
        // and convert it to a minimal array with only the necessary fields for the parent service
        $mini_response = [
            'id' => $response->json('id'),
            'email' => $response->json('email_addresses')[0]['email_address'] ?? null,
            'first_name' => $response->json('first_name'),
            'last_name' => $response->json('last_name'),
        ];
        return $mini_response;
    }

    public function getChild($childId)
    {
        $response = Http::withToken($this->authToken)->get("{$this->baseChildServiceUrl}/api/internal/get-child/{$childId}");
        if ($response->failed() || $response->json('status') !== 'success' || !isset($response->json()['data'])) {
            throw new Exception("Failed to fetch child data: " . $response->body());
        }
        return $response->json()['data'];
    }
}
