<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cover_media_id',
        'title',
        'slug',
        'summary',
        'body',
        'status',
        'role',
        'started_at',
        'completed_at',
        'repository_url',
        'demo_url',
        'is_featured',
        'display_order',
        'views_count',
        'seo_title',
        'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'started_at'   => 'date',
            'completed_at' => 'date',
            'is_featured'   => 'boolean',
            'display_order' => 'integer',
            'views_count'   => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'project_technology');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
