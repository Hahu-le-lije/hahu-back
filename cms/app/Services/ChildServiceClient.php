<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChildServiceClient
{
    protected string $baseUrl;
    protected string $secretToken;

    public function __construct()
    {
        $this->baseUrl = config('services.child_service.url', 'http://localhost:8001');
        $this->secretToken = config('services.child_service.secret_token', '');
    }

    /**
     * Verify parent owns the child
     * 
     * @param string $childId
     * @param string $parentId
     * @return bool
     */
    public function verifyParentOwnsChild(string $childId, string $parentId): bool
    {
        try {
            $response = Http::withToken($this->secretToken)
                ->timeout(5)
                ->get("{$this->baseUrl}/api/internal/children/{$childId}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
                    $child = $data['data'];
                    return isset($child['parent_id']) && $child['parent_id'] === $parentId;
                }
            }

            Log::warning("Child service verification failed for child: {$childId}, status: {$response->status()}");
            return false;
        } catch (\Throwable $e) {
            Log::error("Child service client error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get child details
     * 
     * @param string $childId
     * @return array|null
     */
    public function getChildDetails(string $childId): ?array
    {
        try {
            $response = Http::withToken($this->secretToken)
                ->timeout(5)
                ->get("{$this->baseUrl}/api/internal/children/{$childId}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success' && isset($data['data'])) {
                    return $data['data'];
                }
            }

            return null;
        } catch (\Throwable $e) {
            Log::error("Failed to fetch child details: " . $e->getMessage());
            return null;
        }
    }
}
