<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class InternalUserService
{
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.user_service.url');
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
            'link_child_subscription' => $this->linkChildSubscription($payload['child_id'], $payload['subscription_id']),
            default => throw new Exception("Unknown action: {$action}"),
        };
    }

    protected function getParent($userId)
    {
        $response = Http::post("{$this->baseUrl}/get-parent", ['parent_id' => $userId]);
        return $response->json();
    }

    protected function getChild($childId)
    {
        $response = Http::post("{$this->baseUrl}/get-child", ['child_id' => $childId]);
        return $response->json();
    }

    protected function linkChildSubscription($childId, $subscriptionId)
    {
        $response = Http::post("{$this->baseUrl}/link-subscription", [
            'child_id' => $childId,
            'subscription_id' => $subscriptionId
        ]);
        return $response->json();
    }
}
