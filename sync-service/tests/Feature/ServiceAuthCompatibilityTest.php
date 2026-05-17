<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceAuthCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_protected_ai_routes_accept_internal_service_token(): void
    {
        config(['services.internal_service_tokens' => ['internal-secret']]);

        $this
            ->withToken('internal-secret')
            ->getJson('/api/ai/children/child_123/feature-snapshot')
            ->assertNotFound()
            ->assertJsonPath('message', 'No data available for AI export');
    }

    public function test_protected_ai_routes_still_reject_invalid_token(): void
    {
        config(['services.internal_service_tokens' => ['internal-secret']]);

        $this
            ->withToken('wrong-secret')
            ->getJson('/api/ai/children/child_123/feature-snapshot')
            ->assertUnauthorized();
    }
}
