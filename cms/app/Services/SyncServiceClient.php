<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncServiceClient
{
    protected string $baseUrl;
    protected string $secretToken;

    public function __construct()
    {
        $this->baseUrl = config('services.sync_service.url', 'http://localhost:8002');
        $this->secretToken = config('services.service_auth.sync', config('services.sync_service.secret_token', ''));
    }

    /**
     * Get latest daily summary for a child
     * 
     * @param string $childId
     * @return array|null
     */
    public function getLatestDailySummary(string $childId): ?array
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/api/children/{$childId}/daily-summary");

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? $data;
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Sync service unavailable for child {$childId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get latest summaries (game type breakdowns)
     * 
     * @param string $childId
     * @return array|null
     */
    public function getLatestSummaries(string $childId): ?array
    {
        try {
            $response = Http::timeout(5)
                ->get("{$this->baseUrl}/api/children/{$childId}/summaries/latest");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Sync service summaries unavailable for child {$childId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get feature snapshot with AI analytics
     * 
     * @param string $childId
     * @return array|null
     */
    public function getFeatureSnapshot(string $childId): ?array
    {
        try {
            $response = Http::withToken($this->secretToken)
                ->timeout(5)
                ->get("{$this->baseUrl}/api/ai/children/{$childId}/feature-snapshot");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Sync service feature snapshot unavailable: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get recent learning events
     * 
     * @param string $childId
     * @param string $sinceDate ISO 8601 date string
     * @return array|null
     */
    public function getRecentEvents(string $childId, string $sinceDate): ?array
    {
        try {
            $response = Http::withToken($this->secretToken)
                ->timeout(5)
                ->get("{$this->baseUrl}/api/ai/children/{$childId}/events", ['since' => $sinceDate]);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Throwable $e) {
            Log::warning("Sync service events unavailable: " . $e->getMessage());
            return null;
        }
    }
}
