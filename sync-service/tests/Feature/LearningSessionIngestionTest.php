<?php

namespace Tests\Feature;

use App\Jobs\ProcessLearningEventJob;
use App\Models\LearningEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LearningSessionIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_frontend_can_submit_learning_sessions(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/sessions', [
            'sessions' => [
                [
                    'id' => 'session_001',
                    'childId' => 'child_123',
                    'gameType' => 'phonics',
                    'contentId' => 'lesson_abc',
                    'score' => 80,
                    'timeSpent' => 300,
                    'totalQuestions' => 10,
                    'correctAnswers' => 8,
                    'skillBreakdown' => [
                        'letter_sounds' => 0.8,
                    ],
                    'createdAt' => '2026-05-10T10:15:00Z',
                ],
            ],
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('accepted', 1)
            ->assertJsonPath('created', 1)
            ->assertJsonPath('duplicates', 0);

        $this->assertDatabaseHas('learning_events', [
            'event_id' => 'session_001',
            'child_id' => 'child_123',
            'game_type' => 'phonics',
            'content_id' => 'lesson_abc',
            'score' => 80,
            'time_spent' => 300,
        ]);

        $event = LearningEvent::query()->where('event_id', 'session_001')->firstOrFail();

        $this->assertSame(10, $event->metrics['total_questions']);
        $this->assertSame(8, $event->metrics['correct_answers']);
        $this->assertSame(0.8, $event->skill_breakdown['letter_sounds']);

        Queue::assertPushed(ProcessLearningEventJob::class);
    }

    public function test_learning_event_alias_accepts_same_payload(): void
    {
        Queue::fake();

        $this->postJson('/api/learning-events', [
            'sessions' => [
                [
                    'event_id' => 'evt_001',
                    'child_id' => 'child_123',
                    'game_type' => 'sight_words',
                    'content_id' => 'content_123',
                ],
            ],
        ])->assertAccepted();

        $this->assertDatabaseHas('learning_events', [
            'event_id' => 'evt_001',
            'child_id' => 'child_123',
        ]);
    }

    public function test_duplicate_sessions_are_accepted_without_dispatching_again(): void
    {
        Queue::fake();

        LearningEvent::query()->create([
            'event_id' => 'session_001',
            'child_id' => 'child_123',
            'game_type' => 'phonics',
            'content_id' => 'lesson_abc',
            'score' => 80,
            'time_spent' => 300,
            'metrics' => [],
            'skill_breakdown' => [],
            'event_created_at' => now(),
            'last_updated' => now(),
            'synced_at' => now(),
        ]);

        $this->postJson('/api/sessions', [
            'sessions' => [
                [
                    'id' => 'session_001',
                    'childId' => 'child_123',
                    'gameType' => 'phonics',
                    'contentId' => 'lesson_abc',
                ],
            ],
        ])
            ->assertAccepted()
            ->assertJsonPath('accepted', 1)
            ->assertJsonPath('created', 0)
            ->assertJsonPath('duplicates', 1);

        Queue::assertNotPushed(ProcessLearningEventJob::class);
    }

    public function test_sessions_payload_is_required(): void
    {
        $this
            ->postJson('/api/sessions', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sessions');
    }
}
