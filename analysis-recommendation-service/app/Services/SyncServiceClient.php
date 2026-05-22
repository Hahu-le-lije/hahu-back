<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SyncServiceClient
{
    protected string $baseUrl;
    protected string $serviceToken;

    public function __construct()
    {
        $this->baseUrl = config('services.sync.url', 'http://localhost:8000');
        // In reality, fetch/generate this JWT securely via your auth mechanism
        $this->serviceToken = config('services.sync.secret_token'); 
    }

    public function getAnalyticsOverview(string $childId): ?array
    {
        $response = Http::withToken($this->serviceToken)
            ->get("{$this->baseUrl}/api/children/{$childId}/analytics-overview");
        return $response->successful() ? $response->json() : null;
    }

    public function getFeatureSnapshot(string $childId): ?array
    {
        $response = Http::withToken($this->serviceToken)
            ->get("{$this->baseUrl}/api/ai/children/{$childId}/feature-snapshot");
        return $response->successful() ? $response->json() : null;
    }

    public function getRecentEvents(string $childId, string $sinceDate): ?array
    {
        $response = Http::withToken($this->serviceToken)
            ->get("{$this->baseUrl}/api/ai/children/{$childId}/events", ['since' => $sinceDate]);
        return $response->successful() ? $response->json() : null;
    }
}