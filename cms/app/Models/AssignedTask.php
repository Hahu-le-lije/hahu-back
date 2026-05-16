<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssignedTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'content_id',
        'game_type_id',
        'status',
        'assigned_by',
        'assigned_at',
        'completed_at',
        'reason',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
