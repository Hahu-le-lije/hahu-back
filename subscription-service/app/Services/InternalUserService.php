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

    public function getParent($userId)
    {
        $response = Http::withToken($this->authToken)->get("{$this->baseParentServiceUrl}/api/internal/get-parent/{$userId}");
        if ($response->failed() || $response->json('status') !== 'success' || !isset($response->json()['data'])) {
            throw new Exception("Failed to fetch parent data: " . $response->body());
        }
        return $response->json()['data'];
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
