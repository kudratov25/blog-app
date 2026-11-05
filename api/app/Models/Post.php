<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'author_id',
        'category_id',
        'cover_media_id',
        'title',
        'slug',
        'excerpt',
        'body',
        'status',
        'is_featured',
        'reading_time',
        'views_count',
        'published_at',
        'scheduled_at',
        'seo_title',
        'seo_description',
        'canonical_url',
    ];

    protected function casts(): array
    {
        return [
            'is_featured'  => 'boolean',
            'reading_time' => 'integer',
            'views_count'  => 'integer',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tag');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PostRevision::class)->latest();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
                     ->where('published_at', '<=', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Calculate reading time in minutes based on word count (200 wpm).
     */
    public function calculateReadingTime(): int
    {
        $wordCount = str_word_count(strip_tags($this->body));
        return max(1, (int) ceil($wordCount / 200));
    }
}
