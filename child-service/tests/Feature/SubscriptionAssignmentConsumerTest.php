<?php

namespace Tests\Feature;

use App\Jobs\AssignSubscriptionToChild;
use App\Jobs\LinkChildSubscription;
use App\Models\Child;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SubscriptionAssignmentConsumerTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_service_can_queue_child_subscription_assignment(): void
    {
        Queue::fake();

        config(['child_service.subscription_service_token' => 'subscription-secret']);

        $response = $this
            ->withToken('subscription-secret')
            ->postJson('/api/internal/subscriptions/assign-child', [
                'child_id' => 123,
                'subscription_id' => 'sub_abc123',
            ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('message', 'Subscription assignment queued.');

        Queue::assertPushed(AssignSubscriptionToChild::class, function (AssignSubscriptionToChild $job) {
            return $job->childId === 123
                && $job->subscriptionId === 'sub_abc123';
        });
    }

    public function test_subscription_assignment_job_updates_the_child_subscription_id(): void
    {
        $child = Child::query()->create([
            'parent_id' => 'user_parent123',
            'first_name' => 'Lina',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
            'subscription_id' => null,
        ]);

        (new AssignSubscriptionToChild($child->id, 'sub_abc123'))->handle();

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'subscription_id' => 'sub_abc123',
        ]);
    }

    public function test_link_child_subscription_job_updates_the_child_subscription_id(): void
    {
        $child = Child::query()->create([
            'parent_id' => 'user_parent123',
            'first_name' => 'Lina',
            'username' => 'lina_reader',
            'password' => '123456',
            'status' => 'active',
            'subscription_id' => null,
        ]);

        (new LinkChildSubscription((string) $child->id, 45))->handle();

        $this->assertDatabaseHas('children', [
            'id' => $child->id,
            'subscription_id' => '45',
        ]);
    }

    public function test_subscription_assignment_endpoint_rejects_invalid_service_token(): void
    {
        config(['child_service.subscription_service_token' => 'subscription-secret']);

        $this
            ->withToken('wrong-secret')
            ->postJson('/api/internal/subscriptions/assign-child', [
                'child_id' => 123,
                'subscription_id' => 'sub_abc123',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid subscription service token.');
    }
}
