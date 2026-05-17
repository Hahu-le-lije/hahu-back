<?php

namespace Tests\Feature;

use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalChildApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['child_service.internal_service_token' => 'internal-secret']);
    }

    public function test_internal_services_can_fetch_child_profile(): void
    {
        $child = Child::query()->create([
            'parent_id' => 'user_parent123',
            'first_name' => 'Lina',
            'last_name' => 'Reader',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
            'subscription_id' => 'sub_abc123',
        ]);

        $this
            ->withToken('internal-secret')
            ->getJson("/api/internal/children/{$child->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.id', (string) $child->id)
            ->assertJsonPath('data.parent_id', 'user_parent123')
            ->assertJsonPath('data.child_name', 'Lina Reader')
            ->assertJsonMissingPath('data.password');
    }

    public function test_internal_get_child_alias_matches_subscription_service_expectation(): void
    {
        $child = Child::query()->create([
            'parent_id' => 'user_parent123',
            'first_name' => 'Lina',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
        ]);

        $this
            ->withToken('internal-secret')
            ->getJson("/api/internal/get-child/{$child->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.child_id', (string) $child->id);
    }

    public function test_internal_services_can_list_children_for_subscription(): void
    {
        Child::query()->create([
            'parent_id' => 'user_parent123',
            'first_name' => 'Lina',
            'last_name' => 'Reader',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
            'subscription_id' => 'sub_abc123',
        ]);

        Child::query()->create([
            'parent_id' => 'user_parent456',
            'first_name' => 'Other',
            'username' => 'other_child',
            'password' => '123456',
            'status' => 'active',
            'subscription_id' => 'sub_other',
        ]);

        $this
            ->withToken('internal-secret')
            ->getJson('/api/internal/subscriptions/children/sub_abc123')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.child_name', 'Lina Reader');
    }

    public function test_internal_child_endpoints_reject_invalid_token(): void
    {
        $this
            ->withToken('wrong-secret')
            ->getJson('/api/internal/children/1')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid internal service token.');
    }
}
