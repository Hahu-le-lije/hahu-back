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

    /**
     * Relationship: A subscription belongs to an owner (User)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Relationship: A subscription has MANY children.
     * (One-to-Many)
     */
    // public function children(): HasMany
    // {
    //     return $this->hasMany(Child::class, 'subscription_id');
    // }

    use HasFactory;

    
}