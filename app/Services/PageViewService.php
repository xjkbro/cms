<?php

namespace App\Services;

use App\Models\Post;
use App\Models\PostView;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;

class PageViewService
{
    private const CACHE_PREFIX = 'post_views:';
    private const DAILY_CACHE_PREFIX = 'post_views_daily:';
    private const RECENT_VIEW_PREFIX = 'recent_view:';
    
    private bool $useRedis;
    
    public function __construct()
    {
        // Check if Redis is configured and available
        $this->useRedis = $this->isRedisAvailable();
    }
    
    /**
     * Check if Redis is available and configured
     */
    private function isRedisAvailable(): bool
    {
        try {
            return config('database.redis.default.host') !== null && 
                   config('cache.default') === 'redis' &&
                   Cache::store('redis')->get('test') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Track a page view for a post
     */
    public function trackView(Post $post, Request $request): bool
    {
        $ipAddress = $this->getIpAddress($request);
        $userId = $request->user()?->id;
        $userAgent = $request->userAgent();
        $referer = $request->header('referer');
        
        // Check for recent view to prevent spam
        if ($this->hasRecentView($post->id, $ipAddress, $userId)) {
            return false;
        }
        
        // Mark this IP/user as having viewed recently
        $this->markRecentView($post->id, $ipAddress, $userId);
        
        if ($this->useRedis) {
            // Use Redis for high-performance counting
            $this->incrementRedisCounter($post->id);
            $this->queueViewRecord($post->id, $ipAddress, $userId, $userAgent, $referer);
        } else {
            // Direct database approach - simpler but works perfectly fine
            $this->recordViewDirectly($post, $ipAddress, $userId, $userAgent, $referer);
        }
        
        return true;
    }
    
    /**
     * Get total views for a post
     */
    public function getViewsCount(int $postId): int
    {
        if ($this->useRedis) {
            return (int) Cache::remember(
                self::CACHE_PREFIX . $postId,
                now()->addMinutes(5),
                function () use ($postId) {
                    $post = Post::find($postId);
                    return $post ? $post->views_count : 0;
                }
            );
        } else {
            // Direct database query - simple and reliable
            $post = Post::find($postId);
            return $post ? $post->views_count : 0;
        }
    }
    
    /**
     * Get today's views for a post
     */
    public function getTodayViewsCount(int $postId): int
    {
        if ($this->useRedis) {
            $cacheKey = self::DAILY_CACHE_PREFIX . date('Y-m-d') . ':' . $postId;
            
            return (int) Cache::remember(
                $cacheKey,
                now()->endOfDay(),
                function () use ($postId) {
                    return PostView::where('post_id', $postId)
                        ->whereDate('created_at', today())
                        ->count();
                }
            );
        } else {
            // Direct database query
            return PostView::where('post_id', $postId)
                ->whereDate('created_at', today())
                ->count();
        }
    }
    
    /**
     * Get popular posts by views
     */
    public function getPopularPosts(int $limit = 10, string $period = 'all'): \Illuminate\Database\Eloquent\Collection
    {
        $query = Post::with(['user', 'category', 'project']);
        
        switch ($period) {
            case 'today':
                return $query->withCount(['views as today_views_count' => function ($query) {
                    $query->whereDate('created_at', today());
                }])->orderBy('today_views_count', 'desc')->limit($limit)->get();
                
            case 'week':
                return $query->withCount(['views as week_views_count' => function ($query) {
                    $query->where('created_at', '>=', now()->startOfWeek());
                }])->orderBy('week_views_count', 'desc')->limit($limit)->get();
                
            case 'month':
                return $query->withCount(['views as month_views_count' => function ($query) {
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                }])->orderBy('month_views_count', 'desc')->limit($limit)->get();
                
            default:
                return $query->orderBy('views_count', 'desc')->limit($limit)->get();
        }
    }
    
    /**
     * Sync Redis counters to database (only needed when using Redis)
     */
    public function syncCountersToDatabase(): void
    {
        if (!$this->useRedis) {
            // No syncing needed in database-only mode
            return;
        }
        
        try {
            $redis = Redis::connection();
            $pattern = self::CACHE_PREFIX . '*';
            $keys = $redis->keys($pattern);
            
            foreach ($keys as $key) {
                $postId = str_replace(self::CACHE_PREFIX, '', $key);
                $redisCount = (int) Cache::get($key, 0);
                
                if ($redisCount > 0) {
                    Post::where('id', $postId)->increment('views_count', $redisCount);
                    Cache::forget($key); // Clear the counter after syncing
                }
            }
        } catch (\Exception $e) {
            logger()->warning('Redis sync failed for page views: ' . $e->getMessage());
        }
    }
    
    /**
     * Get client IP address
     */
    private function getIpAddress(Request $request): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $request->ip();
    }
    
    /**
     * Record view directly to database (non-Redis mode)
     */
    private function recordViewDirectly(Post $post, string $ipAddress, ?int $userId, ?string $userAgent, ?string $referer): void
    {
        // Create the view record
        PostView::create([
            'post_id' => $post->id,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referer' => $referer,
            'viewed_at' => now(),
        ]);
        
        // Immediately increment the counter
        $post->increment('views_count');
    }

    /**
     * Check if this IP/user has viewed recently
     */
    private function hasRecentView(int $postId, string $ipAddress, ?int $userId): bool
    {
        if ($this->useRedis) {
            $key = self::RECENT_VIEW_PREFIX . $postId . ':' . $ipAddress . ($userId ? ':' . $userId : '');
            return Cache::has($key);
        } else {
            // Database-only check: look for views in the last hour
            return PostView::where('post_id', $postId)
                ->where('ip_address', $ipAddress)
                ->when($userId, fn($query) => $query->where('user_id', $userId))
                ->where('created_at', '>', now()->subHour())
                ->exists();
        }
    }
    
    /**
     * Mark IP/user as having viewed recently
     */
    private function markRecentView(int $postId, string $ipAddress, ?int $userId): void
    {
        if ($this->useRedis) {
            $key = self::RECENT_VIEW_PREFIX . $postId . ':' . $ipAddress . ($userId ? ':' . $userId : '');
            Cache::put($key, true, now()->addHour()); // 1 hour cooldown
        }
        // For non-Redis mode, the database check in hasRecentView() is sufficient
    }
    
    /**
     * Increment Redis counter
     */
    private function incrementRedisCounter(int $postId): void
    {
        $key = self::CACHE_PREFIX . $postId;
        Cache::increment($key, 1);
        
        // Set expiry if this is a new key
        if (!Cache::has($key . ':ttl')) {
            Cache::put($key . ':ttl', true, now()->addMinutes(10));
        }
    }
    
    /**
     * Queue view record for database insertion
     */
    private function queueViewRecord(int $postId, string $ipAddress, ?int $userId, ?string $userAgent, ?string $referer): void
    {
        // For now, insert directly. In production, you'd dispatch a job:
        // dispatch(new RecordPageView($postId, $ipAddress, $userId, $userAgent, $referer));
        
        PostView::create([
            'post_id' => $postId,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'referer' => $referer,
            'viewed_at' => now(),
        ]);
    }
}