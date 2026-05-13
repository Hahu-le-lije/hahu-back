<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class UserServiceClient
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.user_service.url', 'http://user-service');
    }

    /**
     * Gets child info, caches it for 12 hours to reduce network calls.
     */
    public function getChildInfo(string $childId): ?array
    {
        return Cache::remember("child_info_{$childId}", now()->addHours(12), function () use ($childId) {
            $response = Http::get("{$this->baseUrl}/api/children/{$childId}");
            return $response->successful() ? $response->json('data') : null;
        });
    }

    /**
     * Used by the Cron Job to get all active children.
     * We don't heavily cache this one because the list of active users changes often.
     */
    public function getAllActiveChildren(): array
    {
        $response = Http::get("{$this->baseUrl}/api/children/active");
        return $response->successful() ? $response->json('data') :[];
    }
}