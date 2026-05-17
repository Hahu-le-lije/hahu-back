<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'game_type_id',
        'game_type_name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}