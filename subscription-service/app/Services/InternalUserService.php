<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class InternalUserService
{
    protected string $baseUrl;
    protected string $authToken;

    public function __construct()
    {
        $this->baseUrl = config('services.user_service.url');
        $this->authToken = env('INTERNAL_SERVICE_TOKEN', '');
    }

    /**
     * Mimics RabbitRpcClient::call signature for easier transition.
     *
     * @param string $queue
     * @param array $payload
     * @return array|null
     * @throws Exception
     */
    public function call(string $queue, array $payload)
    {
        $action = $payload['action'] ?? null;

        return match ($action) {
            'get_parent' => $this->getParent($payload['parent_id']),
            'get_child' => $this->getChild($payload['child_id']),
            default => throw new Exception("Unknown action: {$action}"),
        };
    }

    public function getParent($userId)
    {
        $response = Http::withToken($this->authToken)->post("{$this->baseUrl}/get-parent", ['parent_id' => $userId]);
        return $response->json();
    }

    public function getChild($childId)
    {
        $response = Http::withToken($this->authToken)->post("{$this->baseUrl}/get-child", ['child_id' => $childId]);
        return $response->json();
    }
}
