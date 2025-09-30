<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    //
    protected $fillable = [
        'user_id',
        'project_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'feature_image_url',
        'content',
        'is_draft',
        'views_count',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
    ];



    protected static function booted()
    {
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function views()
    {
        return $this->hasMany(PostView::class);
    }

    // Multi-author relationships
    public function authors()
    {
        return $this->belongsToMany(User::class, 'post_authors')
            ->withPivot(['contribution_type', 'order'])
            ->withTimestamps()
            ->orderBy('post_authors.order');
    }

    public function primaryAuthor()
    {
        return $this->authors()->wherePivot('contribution_type', 'primary')->first() ?? $this->user;
    }

    public function coAuthors()
    {
        return $this->authors()->wherePivot('contribution_type', '!=', 'primary');
    }

    // Helper methods for author management
    public function addAuthor(User $user, string $contributionType = 'co-author', int $order = 0)
    {
        return $this->authors()->attach($user->id, [
            'contribution_type' => $contributionType,
            'order' => $order,
        ]);
    }

    public function removeAuthor(User $user)
    {
        return $this->authors()->detach($user->id);
    }

    public function hasAuthor(User $user): bool
    {
        return $this->user_id === $user->id || $this->authors->contains($user->id);
    }

    // View tracking methods
    public function incrementViews(string $ipAddress, int $userId = null, string $userAgent = null, string $referer = null): bool
    {
        // Check if this IP/user combo has viewed recently (prevent spam)
        if (PostView::hasRecentView($this->id, $ipAddress, $userId)) {
            return false; // Don't count duplicate views
        }

        // Create view record
        PostView::createView($this->id, $ipAddress, $userId, $userAgent, $referer);

        // Increment cached counter
        $this->increment('views_count');

        return true;
    }

    public function getViewsCount(): int
    {
        return $this->views_count ?? 0;
    }

    public function getTodayViewsCount(): int
    {
        return $this->views()->whereDate('created_at', today())->count();
    }

    public function getThisWeekViewsCount(): int
    {
        return $this->views()->where('created_at', '>=', now()->startOfWeek())->count();
    }

    public function getThisMonthViewsCount(): int
    {
        return $this->views()->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }
}
