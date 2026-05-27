<?php

namespace App\Jobs;

use App\Models\Child;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LinkChildSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    protected $childId;
    protected $subscriptionId;

    public function __construct($childId, $subscriptionId)
    {
        $this->childId = $childId;
        $this->subscriptionId = $subscriptionId;
    }

    public function handle(): void
    {
        Child::query()
            ->whereKey($this->childId)
            ->firstOrFail()
            ->update([
                'subscription_id' => (string) $this->subscriptionId,
            ]);
    }
}
