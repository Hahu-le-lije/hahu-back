<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable =[
        'child_id', 
        'tier', 
        'recommendation_text', 
        'next_update_expected_at'
    ];
    
    protected $casts =[
        'next_update_expected_at' => 'datetime',
    ];
}