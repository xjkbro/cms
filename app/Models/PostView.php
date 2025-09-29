<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostView extends Model
{
    protected $fillable = [
        'post_id',
        'user_id',
        'ip_address',
        'user_agent',
        'referer',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this IP address has already viewed this post recently (within 1 hour)
     */
    public static function hasRecentView(int $postId, string $ipAddress, int $userId = null): bool
    {
        $query = static::where('post_id', $postId)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>', now()->subHour());

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->exists();
    }

    /**
     * Create a new view record
     */
    public static function createView(int $postId, string $ipAddress, int $userId = null, string $userAgent = null, string $referer = null): self
    {
        return static::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referer' => $referer,
            'viewed_at' => now(),
        ]);
    }
}
