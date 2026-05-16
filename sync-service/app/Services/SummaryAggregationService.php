<?php

namespace App\Services;

use App\Analytics\LiteracyAnalyticsService;
use App\Models\DailySummary;
use App\Models\LearningEvent;
use App\Models\WeeklySummary;
use Carbon\Carbon;

class SummaryAggregationService
{
    public function __construct(
        protected LiteracyAnalyticsService $analytics,
        protected ExplanationGeneratorService $explanations
    ) {}

    public function processEvent(
        LearningEvent $event
    ): void {

        $this->updateDailySummary($event);

        $this->updateWeeklySummary($event);
    }

    protected function updateDailySummary(
        LearningEvent $event
    ): void {

        $date = Carbon::parse(
            $event->event_created_at
        )->toDateString();

        $summary = DailySummary::firstOrCreate(
            [
                'child_id' => $event->child_id,
                'summary_date' => $date,
            ]
        );

        $metrics = $event->metrics ?? [];

        $totalQuestions =
            $summary->total_questions +
            ($metrics['total_questions'] ?? 0);

        $correctAnswers =
            $summary->correct_answers +
            ($metrics['correct_answers'] ?? 0);

        $totalSessions =
            $summary->total_sessions + 1;

        $timeSpent =
            $summary->time_spent +
            $event->time_spent;

        $accuracy =
            $this->analytics->calculateAccuracy(
                $correctAnswers,
                $totalQuestions
            );

        // Simplified placeholders
        $consistency = 1.0;

        $skillDiversity =
            count($event->skill_breakdown ?? []) / 10;

        $masteryScore =
            $this->analytics->calculateMasteryScore(
                $accuracy,
                $consistency,
                $skillDiversity
            );

        $summary->update([
            'total_sessions' => $totalSessions,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy' => $accuracy,
            'time_spent' => $timeSpent,
            'consistency' => $consistency,
            'skill_diversity' => $skillDiversity,
            'mastery_score' => $masteryScore,

            'generated_explanation' =>
                $this->explanations->generate(
                    $accuracy,
                    $masteryScore
                ),
        ]);
    }

    protected function updateWeeklySummary(
        LearningEvent $event
    ): void {

        $weekStart = Carbon::parse(
            $event->event_created_at
        )->startOfWeek()->toDateString();

        $weekEnd = Carbon::parse(
            $event->event_created_at
        )->endOfWeek()->toDateString();

        $summary = WeeklySummary::firstOrCreate(
            [
                'child_id' => $event->child_id,
                'week_start_date' => $weekStart,
            ],
            [
                'week_end_date' => $weekEnd,
            ]
        );

        $metrics = $event->metrics ?? [];

        $totalQuestions =
            $summary->total_questions +
            ($metrics['total_questions'] ?? 0);

        $correctAnswers =
            $summary->correct_answers +
            ($metrics['correct_answers'] ?? 0);

        $totalSessions =
            $summary->total_sessions + 1;

        $timeSpent =
            $summary->time_spent +
            $event->time_spent;

        $accuracy =
            $this->analytics->calculateAccuracy(
                $correctAnswers,
                $totalQuestions
            );

        $consistency = 1.0;

        $skillDiversity =
            count($event->skill_breakdown ?? []) / 10;

        $masteryScore =
            $this->analytics->calculateMasteryScore(
                $accuracy,
                $consistency,
                $skillDiversity
            );

        $summary->update([
            'total_sessions' => $totalSessions,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy' => $accuracy,
            'time_spent' => $timeSpent,
            'consistency' => $consistency,
            'skill_diversity' => $skillDiversity,
            'mastery_score' => $masteryScore,

            'generated_explanation' =>
                $this->explanations->generate(
                    $accuracy,
                    $masteryScore
                ),
        ]);
    }
}
