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
        error_log("SyncServiceClient@getAnalyticsOverview - Fetching analytics for: {$childId}");
        $response = Http::withToken($this->serviceToken)
            ->get("{$this->baseUrl}/api/children/{$childId}/analytics-overview");
        
        if ($response->successful()) {
            error_log("SyncServiceClient@getAnalyticsOverview - Success - Child: {$childId}");
            return $response->json();
        }
        
        error_log("SyncServiceClient@getAnalyticsOverview - Failed - Status: {$response->status()} - Child: {$childId}");
        return null;
    }

    public function getFeatureSnapshot(string $childId): ?array
    {
        error_log("SyncServiceClient@getFeatureSnapshot - Fetching snapshot for: {$childId}");
        $response = Http::withToken($this->serviceToken)
            ->get("{$this->baseUrl}/api/ai/children/{$childId}/feature-snapshot");
            
        if ($response->successful()) {
            error_log("SyncServiceClient@getFeatureSnapshot - Success - Child: {$childId}");
            return $response->json();
        }
        
        error_log("SyncServiceClient@getFeatureSnapshot - Failed - Status: {$response->status()} - Child: {$childId}");
        return null;
    }

    public function getRecentEvents(string $childId, string $sinceDate): ?array
    {
        error_log("SyncServiceClient@getRecentEvents - Fetching events for: {$childId} since: {$sinceDate}");
        $response = Http::withToken($this->serviceToken)
            ->get("{$this->baseUrl}/api/ai/children/{$childId}/events", ['since' => $sinceDate]);
            
        if ($response->successful()) {
            error_log("SyncServiceClient@getRecentEvents - Success - Child: {$childId}");
            return $response->json();
        }
        
        error_log("SyncServiceClient@getRecentEvents - Failed - Status: {$response->status()} - Child: {$childId}");
        return null;
    }
}
