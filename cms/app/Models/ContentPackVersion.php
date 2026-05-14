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
        'size_bytes',
        'payload',
        'min_app_version',
        'published_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'published_at' => 'datetime',
    ];

    public function contentPack(): BelongsTo
    {
        return $this->belongsTo(ContentPack::class);
    }
}
