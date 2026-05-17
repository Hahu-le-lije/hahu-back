<?php

namespace App\Services;

use App\Analytics\LiteracyAnalyticsService;
use App\Models\LearningEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GameTypeSummaryService
{
    public function __construct(private readonly LiteracyAnalyticsService $analytics)
    {
    }

    public function latestForChild(string $childId): array
    {
        return LearningEvent::query()
            ->where('child_id', $childId)
            ->orderByDesc('event_created_at')
            ->get()
            ->groupBy(fn (LearningEvent $event) => $this->gameTypeId($event->game_type))
            ->filter(fn (Collection $events, int|string $gameTypeId) => (int) $gameTypeId > 0)
            ->map(fn (Collection $events, int|string $gameTypeId) => $this->summarize((int) $gameTypeId, $events))
            ->sortBy('game_type_id')
            ->values()
            ->all();
    }

    private function summarize(int $gameTypeId, Collection $events): array
    {
        $totalQuestions = 0;
        $correctAnswers = 0;
        $timeSpent = 0;
        $scoreTotal = 0;
        $scoreCount = 0;
        $skills = [];

        foreach ($events as $event) {
            $metrics = $event->metrics ?? [];
            $skillBreakdown = $event->skill_breakdown ?? [];

            $totalQuestions += (int) ($metrics['total_questions'] ?? 0);
            $correctAnswers += (int) ($metrics['correct_answers'] ?? 0);
            $timeSpent += (int) $event->time_spent;
            $scoreTotal += (int) $event->score;
            $scoreCount++;

            foreach (array_keys($skillBreakdown) as $skill) {
                $skills[$skill] = true;
            }
        }

        $accuracy = $totalQuestions > 0
            ? $this->analytics->calculateAccuracy($correctAnswers, $totalQuestions)
            : $this->scoreAccuracy($scoreTotal, $scoreCount);

        $skillDiversity = $this->analytics->calculateSkillDiversity(count($skills), 10);
        $consistency = 1.0;
        $masteryScore = $this->analytics->calculateMasteryScore($accuracy, $consistency, $skillDiversity);
        $latestEvent = $events->sortByDesc('event_created_at')->first();

        return [
            'game_type_id' => $gameTypeId,
            'game_type' => $this->gameTypeName($gameTypeId),
            'total_sessions' => $events->count(),
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy' => $accuracy,
            'time_spent' => $timeSpent,
            'consistency' => $consistency,
            'skill_diversity' => $skillDiversity,
            'mastery_score' => $masteryScore,
            'last_event_at' => $latestEvent?->event_created_at,
        ];
    }

    private function scoreAccuracy(int $scoreTotal, int $scoreCount): float
    {
        if ($scoreCount === 0) {
            return 0;
        }

        $average = $scoreTotal / $scoreCount;

        if ($average > 1) {
            $average /= 100;
        }

        return round(min(1, max(0, $average)), 4);
    }

    private function gameTypeId(string $gameType): int
    {
        if (is_numeric($gameType)) {
            $id = (int) $gameType;

            return $id >= 1 && $id <= 7 ? $id : 0;
        }

        $normalized = Str::of($gameType)
            ->lower()
            ->replace(['&', '+'], ' and ')
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        return match ($normalized) {
            'fidel_tracing', 'tracing', 'phonics', 'letter_sounds' => 1,
            'fidel_match', 'voice_to_word', 'matching' => 2,
            'pic_to_word', 'picture_to_word', 'sight_words', 'word_recognition' => 3,
            'word_builder', 'builder' => 4,
            'listen_and_fill', 'listen_fill', 'fill_in_the_blank', 'fill_blank' => 5,
            'speak_up', 'pronunciation', 'speech' => 6,
            'story_quiz', 'quiz', 'reading_comprehension' => 7,
            default => 0,
        };
    }

    private function gameTypeName(int $gameTypeId): string
    {
        return [
            1 => 'Fidel Tracing',
            2 => 'Fidel Match',
            3 => 'Pic-to-Word',
            4 => 'Word Builder',
            5 => 'Listen & Fill',
            6 => 'Speak Up',
            7 => 'Story Quiz',
        ][$gameTypeId] ?? 'Unknown';
    }
}
