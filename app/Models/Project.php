<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'slug',
        'user_id',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function publishedPosts(): HasMany
    {
        return $this->hasMany(Post::class)->where('is_draft', false);
    }

    public function draftPosts(): HasMany
    {
        return $this->hasMany(Post::class)->where('is_draft', true);
    }

    public static function defaultForUser($userId): ?Project
    {
        return self::where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }

    public function makeDefault(): void
    {
        // Remove default from other projects
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }
}
