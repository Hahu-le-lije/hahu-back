<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Child;


class Subscription extends Model
{
    
     protected $fillable =[
        'owner_id',
        'plan_type',
        'available_slots', 
        'status',
        'ends_at',         
        'tx_ref',
    ];

    protected $casts = [
        'ends_at' => 'datetime',
    ];

    use HasFactory;

    
}