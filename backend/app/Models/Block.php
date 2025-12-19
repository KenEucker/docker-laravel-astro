<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Orchid\Screen\AsSource;

class Block extends Model
{
    use HasFactory, AsSource;

    protected $fillable = [
        'key',
        'type',
        'status',
        'published_at',
        'data',
        'visibility',
        'locked',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'array',
        'locked' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
        'locked' => false,
    ];

    /**
     * Relationships
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */

    /**
     * Scope to only published blocks that are ready for public consumption.
     * Published status AND published_at is null or in the past.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Scope to filter by visibility (null = public).
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->whereNull('visibility');
    }

    /**
     * Scope to filter by key prefix (useful for fetching groups like "homepage.*").
     */
    public function scopeKeyPrefix(Builder $query, string $prefix): Builder
    {
        return $query->where('key', 'like', $prefix . '%');
    }

    /**
     * Check if block is publicly accessible.
     */
    public function isPublic(): bool
    {
        return $this->visibility === null;
    }

    /**
     * Check if block is currently published and available.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published'
            && ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * Auto-update published_at when publishing.
     */
    protected static function booted()
    {
        static::saving(function (Block $block) {
            // If being published and published_at is null, set it to now
            if ($block->status === 'published' && $block->published_at === null) {
                $block->published_at = now();
            }

            // If being unpublished, clear published_at
            if ($block->status === 'draft' && $block->published_at !== null) {
                $block->published_at = null;
            }
        });
    }
}
