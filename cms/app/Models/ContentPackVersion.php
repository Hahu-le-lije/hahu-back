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
        'meta',        
        'game_type',   
        'content',     
        'published_at',
    ];

    protected $casts = [
        'content'      => 'array', 
        'meta'         => 'array', 
        'published_at' => 'datetime',
    ];

    public function contentPack(): BelongsTo
    {
        return $this->belongsTo(ContentPack::class);
    }
}