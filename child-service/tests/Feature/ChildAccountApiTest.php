<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChildAccountApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_can_create_child_with_generated_credentials(): void
    {
        $response = $this
            ->withToken($this->parentToken('parent-123'))
            ->postJson('/api/parents/children', [
                'first_name' => 'Lina',
                'last_name' => 'Reader',
                'age' => 8,
                'skill_level' => 'beginner',
                'subscription_id' => 'sub_123',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('child.parent_id', 'parent-123')
            ->assertJsonPath('child.first_name', 'Lina')
            ->assertJsonStructure([
                'child' => ['id', 'parent_id', 'first_name', 'username', 'status'],
                'credentials' => ['username', 'pin'],
            ]);

        $child = Child::query()->firstOrFail();

        $this->assertSame($response->json('credentials.username'), $child->username);
        $this->assertTrue(Hash::check($response->json('credentials.pin'), $child->password));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $response->json('credentials.pin'));
    }

    public function test_parent_can_only_see_their_own_children(): void
    {
        Child::query()->create([
            'parent_id' => 'parent-123',
            'first_name' => 'Own',
            'username' => 'own_child',
            'password' => '123456',
            'status' => 'active',
        ]);

        Child::query()->create([
            'parent_id' => 'parent-456',
            'first_name' => 'Other',
            'username' => 'other_child',
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this
            ->withToken($this->parentToken('parent-123'))
            ->getJson('/api/parents/children');

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.username', 'own_child');
    }

    public function test_child_can_login_and_read_profile_but_not_update_itself(): void
    {
        $child = Child::query()->create([
            'parent_id' => 'parent-123',
            'first_name' => 'Lina',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/children/login', [
            'username' => 'lina_reader',
            'password' => '123456',
        ]);

        $login
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'child']);

        $this
            ->withToken($login->json('access_token'))
            ->getJson('/api/children/me')
            ->assertOk()
            ->assertJsonPath('id', $child->id)
            ->assertJsonPath('username', 'lina_reader');

        $this
            ->withToken($login->json('access_token'))
            ->patchJson('/api/children/me', ['first_name' => 'Changed'])
            ->assertMethodNotAllowed();
    }

    public function test_parent_can_rotate_child_credentials(): void
    {
        $child = Child::query()->create([
            'parent_id' => 'parent-123',
            'first_name' => 'Lina',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
        ]);

        $response = $this
            ->withToken($this->parentToken('parent-123'))
            ->postJson("/api/parents/children/{$child->id}/credentials");

        $response
            ->assertOk()
            ->assertJsonPath('credentials.username', 'lina_reader');

        $child->refresh();

        $this->assertTrue(Hash::check($response->json('credentials.pin'), $child->password));
        $this->assertFalse(Hash::check('123456', $child->password));
    }

    private function parentToken(string $parentId): string
    {
        return app(JwtService::class)->issue([
            'sub' => $parentId,
            'role' => 'parent',
        ], config('child_service.parent_jwt_secret'), 10);
    }
}
