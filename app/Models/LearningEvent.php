<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningEvent extends Model
{
    protected $fillable = [
        'event_id',
        'child_id',
        'game_type',
        'content_id',
        'score',
        'time_spent',
        'metrics',
        'skill_breakdown',
        'event_created_at',
        'last_updated',
        'synced_at',
    ];

    protected $casts = [
        'metrics' => 'array',
        'skill_breakdown' => 'array',
        'event_created_at' => 'datetime',
        'last_updated' => 'datetime',
        'synced_at' => 'datetime',
    ];
}
