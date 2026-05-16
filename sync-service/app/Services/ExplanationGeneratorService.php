<?php

namespace App\Services;

class ExplanationGeneratorService
{
    public function generate(
        float $accuracy,
        float $masteryScore
    ): string {

        if ($masteryScore >= 0.85) {
            return 'Excellent literacy progress this period.';
        }

        if ($masteryScore >= 0.70) {
            return 'Strong literacy performance with steady improvement.';
        }

        if ($masteryScore >= 0.50) {
            return 'Moderate literacy progress with room for growth.';
        }

        return 'Additional practice may help improve literacy skills.';
    }
}
