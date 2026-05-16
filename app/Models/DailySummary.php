<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailySummary extends Model
{
    protected $fillable = [
        'child_id',
        'summary_date',
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
        'summary_date' => 'date',
    ];
}
