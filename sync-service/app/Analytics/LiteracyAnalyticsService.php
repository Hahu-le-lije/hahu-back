<?php

namespace App\Analytics;

class LiteracyAnalyticsService
{
    public function calculateAccuracy(
        int $correctAnswers,
        int $totalQuestions
    ): float {

        if ($totalQuestions === 0) {
            return 0;
        }

        return round(
            $correctAnswers / $totalQuestions,
            4
        );
    }

    public function calculateConsistency(
        int $activeDays,
        int $totalDays
    ): float {

        if ($totalDays === 0) {
            return 0;
        }

        return round(
            $activeDays / $totalDays,
            4
        );
    }

    public function calculateSkillDiversity(
        int $masteredSkills,
        int $availableSkills
    ): float {

        if ($availableSkills === 0) {
            return 0;
        }

        return round(
            $masteredSkills / $availableSkills,
            4
        );
    }

    // might want to change this formula later tho
    public function calculateMasteryScore(
        float $accuracy,
        float $consistency,
        float $skillDiversity
    ): float {

        $score =
            (0.5 * $accuracy) +
            (0.3 * $consistency) +
            (0.2 * $skillDiversity);

        return round($score, 4);
    }
}
