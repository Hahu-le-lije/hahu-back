<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LinkChildSubscription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $childId;
    protected $subscriptionId;

    public function __construct($childId, $subscriptionId)
    {
        $this->childId = $childId;
        $this->subscriptionId = $subscriptionId;
    }

    public function handle()
    {
        // NOTE: In a multi-service architecture, if this Job's purpose 
        // is just to PUSH to RabbitMQ for another service to read, 
        // Laravel handles the "pushing" during dispatch. 
        // This handle() method would be empty or used for logging.
    }
}