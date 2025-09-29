# Page Views Configuration

## Database-Only Mode (Default - No Redis Required)

Your page views system is configured to work **without Redis** by default. This is perfect for:
- Getting started quickly
- Small to medium traffic sites
- When you want simplicity over maximum performance

### Current Setup:
- ✅ **Works out of the box** - no additional configuration needed
- ✅ **Simple and reliable** - all data stored in your existing database
- ✅ **Spam protection** - 1-hour cooldown per IP/user combination
- ✅ **Real-time counting** - views are immediately recorded and counted

### How It Works:
1. When someone views a post, it checks the database for recent views from that IP
2. If no recent view found, it creates a new view record and increments the counter
3. All analytics are calculated directly from the database

---

## Enabling Redis (Optional - For High Traffic)

If your site grows and you need better performance, you can enable Redis:

### Step 1: Install Redis
```bash
# Option 1: Docker (easiest)
docker run -d --name redis -p 6379:6379 redis:alpine

# Option 2: Ubuntu/Debian
sudo apt install redis-server

# Option 3: Use managed Redis service (Redis Cloud, AWS ElastiCache, etc.)
```

### Step 2: Configure Laravel
Add to your `.env` file:
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

### Step 3: Add Scheduled Job (optional)
In `app/Console/Kernel.php`, add:
```php
protected function schedule(Schedule $schedule)
{
    // Sync Redis counters to database every 5 minutes
    $schedule->call(function () {
        app(\App\Services\PageViewService::class)->syncCountersToDatabase();
    })->everyFiveMinutes();
}
```

### Redis Benefits:
- ⚡ **Faster**: View counting doesn't hit the database
- 📊 **Better caching**: Analytics queries are cached
- 🚀 **Higher throughput**: Can handle thousands of views per second

---

## Usage Examples

### In Your Controllers:
```php
use App\Services\PageViewService;

public function show(Request $request, Post $post, PageViewService $pageViewService)
{
    // Track the view (works with or without Redis)
    $pageViewService->trackView($post, $request);
    
    // Get analytics
    $viewsCount = $pageViewService->getViewsCount($post->id);
    $todayViews = $pageViewService->getTodayViewsCount($post->id);
    
    return view('post', [
        'post' => $post,
        'viewsCount' => $viewsCount,
        'todayViews' => $todayViews
    ]);
}
```

### Get Popular Posts:
```php
$pageViewService = app(\App\Services\PageViewService::class);

$popularToday = $pageViewService->getPopularPosts(10, 'today');
$popularThisWeek = $pageViewService->getPopularPosts(10, 'week');
$popularAllTime = $pageViewService->getPopularPosts(10, 'all');
```

---

## Migration & Testing

Run the migrations to set up the page views tables:
```bash
php artisan migrate
```

Test the system:
```bash
php artisan test
```

The system will automatically detect if Redis is available and use the appropriate method. **No code changes needed** when switching between database-only and Redis modes!

---

## Performance Comparison

### Database-Only Mode:
- **Suitable for**: Up to ~1000 views per minute
- **Pros**: Simple, reliable, no additional services
- **Cons**: More database queries for high traffic

### Redis Mode:
- **Suitable for**: Unlimited views (scales with Redis capacity)  
- **Pros**: Extremely fast, minimal database load
- **Cons**: Additional service to manage

**Recommendation**: Start with database-only mode. Upgrade to Redis when you need it!
