<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentProject = $request->attributes->get('current_project');
        $timeframe = $request->get('timeframe', 'all'); // 'all', 'year', 'month'
        
        // Get all user IDs who have access to this project
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);
        
        // Basic statistics for the current project (from all collaborators)
        $publishedPosts = Post::whereIn('user_id', $userIds)
            ->where('project_id', $currentProject->id)
            ->where('is_draft', false)
            ->count();
            
        $draftPosts = Post::whereIn('user_id', $userIds)
            ->where('project_id', $currentProject->id)
            ->where('is_draft', true)
            ->count();
            
        $totalCategories = Category::where('project_id', $currentProject->id)->count();
        
        // Posts over time data
        $postsOverTime = $this->getPostsOverTime($userIds, $currentProject->id, $timeframe);
        
        return Inertia::render('dashboard', [
            'stats' => [
                'published_posts' => $publishedPosts,
                'draft_posts' => $draftPosts,
                'total_posts' => $publishedPosts + $draftPosts,
                'categories' => $totalCategories,
            ],
            'posts_over_time' => $postsOverTime,
            'current_timeframe' => $timeframe,
            'current_project' => $currentProject,
        ]);
    }
    
    private function getPostsOverTime($userIds, $projectId, $timeframe)
    {
        $query = Post::whereIn('user_id', $userIds)
            ->where('project_id', $projectId)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'));
            
        switch ($timeframe) {
            case 'year':
                $query->where('created_at', '>=', now()->subYear());
                break;
            case 'month':
                $query->where('created_at', '>=', now()->subMonth());
                break;
            case 'all':
            default:
                // No additional where clause needed
                break;
        }
        
        $data = $query->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
            
        // Fill in missing dates with 0 counts for better visualization
        return $this->fillMissingDates($data, $timeframe);
    }
    
    private function fillMissingDates($data, $timeframe)
    {
        $result = [];
        $startDate = now();
        $endDate = now();
        
        switch ($timeframe) {
            case 'year':
                $startDate = now()->subYear();
                break;
            case 'month':
                $startDate = now()->subMonth();
                break;
            case 'all':
            default:
                if ($data->isEmpty()) {
                    return [];
                }
                $startDate = $data->first()->date ? now()->parse($data->first()->date) : now()->subMonth();
                $endDate = $data->last()->date ? now()->parse($data->last()->date) : now();
                break;
        }
        
        // Create a collection of all dates in range
        $dataByDate = $data->keyBy('date');
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $result[] = [
                'date' => $dateStr,
                'count' => $dataByDate->has($dateStr) ? $dataByDate[$dateStr]->count : 0,
                'formatted_date' => $currentDate->format('M j'),
            ];
            $currentDate->addDay();
        }
        
        return $result;
    }
}
