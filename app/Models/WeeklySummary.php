<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklySummary extends Model
{
    protected $fillable = [
        'child_id',
        'week_start_date',
        'week_end_date',
        'total_sessions',
        'total_questions',
        'correct_answers',
        'accuracy',
        'time_spent',
        'consistency',
        'skill_diversity',
        'mastery_score',
        'generated_explanation',
        'algorithm_version',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];
}
