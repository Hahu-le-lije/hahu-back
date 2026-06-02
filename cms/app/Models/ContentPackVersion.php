<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPackVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_pack_id',
        'version',
        'checksum',
        'meta',        // Ensure this is here
        'game_type',   // Ensure this is here
        'content',     // Ensure this is here
        'published_at',
    ];

    protected $casts = [
        'content'      => 'array', // Use 'content' instead of 'payload'
        'meta'         => 'array', // Cast meta as array
        'published_at' => 'datetime',
    ];

    public function contentPack(): BelongsTo
    {
        return $this->belongsTo(ContentPack::class);
    }
}