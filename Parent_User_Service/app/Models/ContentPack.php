<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContentPack extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'description',
        'game_type',
        'thumbnail_url',
        'size_mb',
        'is_active',
        'latest_published_version',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function versions(): HasMany
    {
        return $this->hasMany(ContentPackVersion::class);
    }

    public function latestPublishedVersion(): HasOne
    {
        return $this->hasOne(ContentPackVersion::class)
            ->ofMany('version', 'max', function ($query) {
                $query->whereNotNull('published_at');
            });
    }
}
