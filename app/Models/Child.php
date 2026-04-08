<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Child extends Model //! might not need this class
{
    protected $fillable =[
        'parent_id',
        'subscription_id', // Directly link the subscription here
        'name',
        'birth_date',
    ];

    

        /**
     * Relationship: A child belongs to a parent (User)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }
    
    /**
     * Relationship: A child belongs to ONE subscription.
     * This makes Eager Loading very clean.
    */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }
    /** @use HasFactory<\Database\Factories\ChildFactory> */
    use HasFactory;
}