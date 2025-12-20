<?php

namespace App\Models;

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

    // ✅ These are what make Orchid filtering/sorting safe & work properly
    // protected array $allowedFilters = [
    //     'key',
    //     'type',
    //     'status',
    //     'visibility',
    //     'locked',
    //     'created_by',
    //     'updated_by',
    //     'published_at',
    //     'created_at',
    //     'updated_at',
    // ];

    protected array $allowedSorts = [
        'key',
        'type',
        'status',
        'published_at',
        'created_at',
        'updated_at',
    ];

    // ...the rest of your model unchanged
}
