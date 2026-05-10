<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeeklySummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'child_id' => $this->child_id,

            'week_start_date' =>
                $this->week_start_date,

            'week_end_date' =>
                $this->week_end_date,

            'total_sessions' =>
                $this->total_sessions,

            'total_questions' =>
                $this->total_questions,

            'correct_answers' =>
                $this->correct_answers,

            'accuracy' => round(
                $this->accuracy * 100,
                2
            ),

            'time_spent' =>
                $this->time_spent,

            'consistency' => round(
                $this->consistency * 100,
                2
            ),

            'skill_diversity' => round(
                $this->skill_diversity * 100,
                2
            ),

            'mastery_score' => round(
                $this->mastery_score * 100,
                2
            ),

            'generated_explanation' =>
                $this->generated_explanation,

            'algorithm_version' =>
                $this->algorithm_version,
        ];
    }
}
