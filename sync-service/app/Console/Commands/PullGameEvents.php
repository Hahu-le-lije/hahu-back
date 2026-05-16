<?php

namespace App\Console\Commands;

use App\Integrations\GameService\GameServiceClient;
use App\Jobs\ProcessLearningEventJob;
use App\Models\LearningEvent;
use App\Models\SyncCheckpoint;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PullGameEvents extends Command
{
    protected $signature = 'sync:game-events';

    protected $description = 'Pull gameplay events from Game Service';

    public function handle(GameServiceClient $client): void
    {
        $this->info('Starting game event sync...');

        $checkpoint = SyncCheckpoint::firstOrCreate(
            ['service_name' => 'game_service'],
            ['last_successful_sync' => null]
        );

        $lastSync = optional(
            $checkpoint->last_successful_sync
        )?->toISOString();

        $events = $client->fetchEvents($lastSync);

        $this->info('Fetched ' . count($events) . ' events');

        foreach ($events as $event) {

            $learningEvent = LearningEvent::updateOrCreate(
                [
                    'event_id' => $event['id'],
                ],
                [
                    'child_id' => $event['child_id'],
                    'game_type' => $event['game_type'],
                    'content_id' => $event['content_id'],
                    'score' => $event['score'],
                    'time_spent' => $event['time_spent'],

                    'metrics' => json_decode(
                        $event['metrics'],
                        true
                    ),

                    'skill_breakdown' => json_decode(
                        $event['skill_breakdown'],
                        true
                    ),

                    'event_created_at' => $event['created_at'],

                    'last_updated' => $event['last_updated'],

                    'synced_at' => now(),
                ]
            );

            ProcessLearningEventJob::dispatch(
                $learningEvent
            );
        }

        $checkpoint->update([
            'last_successful_sync' => Carbon::now(),
        ]);

        $this->info('Sync complete.');
    }
}
