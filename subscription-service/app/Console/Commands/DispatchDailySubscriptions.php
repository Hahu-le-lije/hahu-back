<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Subscription;
use App\Jobs\ProcessActiveSubscriptions;

class DispatchDailySubscriptions extends Command
{
    protected $signature = 'subscriptions:dispatch-daily';
    protected $description = 'Dispatch active subscriptions to the ARS RabbitMQ queue';

    public function handle()
    {
        $this->info('Starting to dispatch daily active subscriptions...');

        // Chunking by 50 to keep RabbitMQ message sizes optimal
        Subscription::query()
            ->where('status', 'active')
            ->where('ends_at', '>', now()) // Ensure it hasn't expired
            ->select('id as subscription_id', 'plan_type as tier') // Alias plan_type to tier
            ->chunk(50, function ($subscriptions) {
                // Dispatch the job to the specific RabbitMQ queue the ARS is listening to
                ProcessActiveSubscriptions::dispatch($subscriptions->toArray())
                    ->onCnnection('rabbitmq')
                    ->onQueue('ars_subscriptions_queue');
            });

        $this->info('Successfully dispatched all active subscriptions to ARS.');
    }
}