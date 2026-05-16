<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Content extends Model
{
    protected $table = 'content';

    protected $fillable = [
        'content_pack_version_id',
        'type',
        'title',
        'description',
        'content',
        'sequence_order',
        'difficulty',
        'is_active',
    ];

    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get the content pack version this content belongs to
     */
    public function contentPackVersion(): BelongsTo
    {
        return $this->belongsTo(ContentPackVersion::class);
    }

    /**
     * Get content pack through version
     */
    public function contentPack()
    {
        return $this->contentPackVersion ? $this->contentPackVersion->contentPack : null;
    }

    /**
     * Scope to get active content only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by content type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by difficulty
     */
    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    /**
     * Get content ordered by sequence
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence_order', 'asc');
    }
}
