<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Block extends Model
{
    use AsSource, Filterable, HasFactory;

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

    protected array $allowedSorts = [
        'key',
        'type',
        'status',
        'published_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Scope: only published blocks.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope: blocks that are public (no visibility restriction).
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->whereNull('visibility');
    }

    /**
     * Scope: key prefix match (e.g. "homepage.").
     */
    public function scopeKeyPrefix(Builder $query, string $prefix): Builder
    {
        return $query->where('key', 'like', $prefix . '%');
    }

    /**
     * Convenience: true if visibility is public.
     */
    public function isPublic(): bool
    {
        return $this->visibility === null;
    }

    public function updatedByUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
