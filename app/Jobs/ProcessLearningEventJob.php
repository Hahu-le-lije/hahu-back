<?php

namespace App\Jobs;

use App\Models\LearningEvent;
use App\Services\SummaryAggregationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessLearningEventJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public LearningEvent $event
    ) {}

    public function handle(
        SummaryAggregationService $aggregator
    ): void {

        $aggregator->processEvent(
            $this->event
        );
    }
}
