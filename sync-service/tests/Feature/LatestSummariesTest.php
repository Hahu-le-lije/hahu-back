<?php

namespace Tests\Feature;

use App\Models\LearningEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestSummariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cms_can_fetch_latest_game_type_summaries(): void
    {
        LearningEvent::query()->create([
            'event_id' => 'evt_1',
            'child_id' => 'child_123',
            'game_type' => 'fidel_tracing',
            'content_id' => 'content_1',
            'score' => 60,
            'time_spent' => 120,
            'metrics' => ['total_questions' => 10, 'correct_answers' => 6],
            'skill_breakdown' => ['letter_sounds' => 0.6],
            'event_created_at' => '2026-05-10T10:00:00Z',
            'last_updated' => '2026-05-10T10:00:00Z',
            'synced_at' => now(),
        ]);

        LearningEvent::query()->create([
            'event_id' => 'evt_2',
            'child_id' => 'child_123',
            'game_type' => 'fidel_tracing',
            'content_id' => 'content_2',
            'score' => 80,
            'time_spent' => 180,
            'metrics' => ['total_questions' => 10, 'correct_answers' => 8],
            'skill_breakdown' => ['blending' => 0.8],
            'event_created_at' => '2026-05-11T10:00:00Z',
            'last_updated' => '2026-05-11T10:00:00Z',
            'synced_at' => now(),
        ]);

        LearningEvent::query()->create([
            'event_id' => 'evt_other_child',
            'child_id' => 'child_456',
            'game_type' => 'fidel_tracing',
            'content_id' => 'content_3',
            'score' => 100,
            'time_spent' => 60,
            'metrics' => ['total_questions' => 10, 'correct_answers' => 10],
            'skill_breakdown' => [],
            'event_created_at' => '2026-05-11T10:00:00Z',
            'last_updated' => '2026-05-11T10:00:00Z',
            'synced_at' => now(),
        ]);

        $this
            ->getJson('/api/children/child_123/summaries/latest')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.game_type_id', 1)
            ->assertJsonPath('0.accuracy', 0.7)
            ->assertJsonPath('0.mastery_score', 0.69)
            ->assertJsonPath('0.total_sessions', 2)
            ->assertJsonPath('0.time_spent', 300);
    }

    public function test_latest_summaries_returns_empty_array_without_known_game_types(): void
    {
        LearningEvent::query()->create([
            'event_id' => 'evt_unknown',
            'child_id' => 'child_123',
            'game_type' => 'unknown_game',
            'content_id' => 'content_1',
            'score' => 60,
            'time_spent' => 120,
            'metrics' => [],
            'skill_breakdown' => [],
            'event_created_at' => now(),
            'last_updated' => now(),
            'synced_at' => now(),
        ]);

        $this
            ->getJson('/api/children/child_123/summaries/latest')
            ->assertOk()
            ->assertExactJson([]);
    }
}
