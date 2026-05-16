<?php

namespace App\Jobs;

use App\Models\Child;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssignSubscriptionToChild implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly int $childId,
        public readonly string $subscriptionId,
    ) {}

    public function handle(): void
    {
        Child::query()
            ->whereKey($this->childId)
            ->firstOrFail()
            ->update([
                'subscription_id' => $this->subscriptionId,
            ]);
    }
}
