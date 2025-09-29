<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Services\PageViewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentProject = $request->attributes->get('current_project');

        // Check if user has access to this project
        if (!$currentProject->canUserView($request->user())) {
            abort(403);
        }

        // Get all user IDs who have access to this project
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        return Inertia::render('posts/posts', [
            'posts' => Post::with(['category', 'user', 'tags', 'authors'])
                ->whereIn('user_id', $userIds)
                ->where('project_id', $currentProject->id)
                ->get(),
            'categories' => Category::whereIn('user_id', $userIds)
                ->where('project_id', $currentProject->id)
                ->get(),
            'tags' => \App\Models\Tag::all(),
            'canEdit' => $currentProject->canUserEdit($request->user()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $currentProject = $request->attributes->get('current_project');

        // Check if user can edit in this project
        if (!$currentProject->canUserEdit($request->user())) {
            abort(403);
        }

        // Get categories from all project collaborators
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        $categories = \App\Models\Category::whereIn('user_id', $userIds)
            ->where('project_id', $currentProject->id)
            ->get();

        return Inertia::render('posts/edit', [
            'categories' => $categories,
            'existingTags' => [],
            'project' => $currentProject->load('collaborators:id,name,email'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $currentProject = $request->attributes->get('current_project');

        // Check if user can edit in this project
        if (!$currentProject->canUserEdit($request->user())) {
            abort(403);
        }

        // Get all collaborator IDs for validation
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'category_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($currentProject, $userIds) {
                    if ($value && !Category::where('id', $value)
                                          ->whereIn('user_id', $userIds)
                                          ->where('project_id', $currentProject->id)
                                          ->exists()) {
                        $fail('The selected category must belong to the current project.');
                    }
                }
            ],
            'excerpt' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'is_draft' => 'nullable|boolean',
            'authors' => 'nullable|array',
            'authors.*' => 'integer|exists:users,id',
        ]);

        $data['user_id'] = $request->user()->id;
        $data['project_id'] = $currentProject->id;

        $post = \App\Models\Post::create($data);

        // Handle authors
        if ($request->has('authors') && is_array($request->authors)) {
            $authorData = [];
            foreach ($request->authors as $index => $authorId) {
                // Ensure author is part of the project
                if (in_array($authorId, $userIds)) {
                    $authorData[$authorId] = [
                        'contribution_type' => $authorId === $request->user()->id ? 'primary' : 'co-author',
                        'order' => $index,
                    ];
                }
            }
            $post->authors()->sync($authorData);
        }

        // Handle tags
        if ($request->has('tags') && is_array($request->tags)) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = \App\Models\Tag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
            $post->tags()->sync($tagIds);
        }

        return redirect()->route('posts');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Post $post, PageViewService $pageViewService)
    {
        $currentProject = $request->attributes->get('current_project');

        // Check if user has access to this project
        if (!$currentProject || $post->project_id !== $currentProject->id) {
            abort(404);
        }

        if (!$currentProject->canUserView($request->user())) {
            abort(403);
        }

        // Track page view (only for non-draft posts)
        if (!$post->is_draft) {
            $pageViewService->trackView($post, $request);
        }

        // Load relationships
        $post->load(['category', 'user', 'tags', 'authors', 'project']);

        return Inertia::render('posts/show', [
            'post' => $post,
            'viewsCount' => $pageViewService->getViewsCount($post->id),
            'todayViews' => $pageViewService->getTodayViewsCount($post->id),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Post $post)
    {
        $currentProject = $request->attributes->get('current_project');

        // Check if post belongs to current project
        if ($post->project_id !== $currentProject->id) {
            abort(404);
        }

        // Check if user can edit this post
        // Post authors, project editors, admins, and owners can edit
        $canEdit = $post->hasAuthor($request->user()) ||
                   $currentProject->canUserEdit($request->user());

        if (!$canEdit) {
            abort(403);
        }

        // Get categories from all project collaborators
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        $categories = \App\Models\Category::whereIn('user_id', $userIds)
            ->where('project_id', $currentProject->id)
            ->get();

        $post->load(['tags', 'authors']);
        $existingTags = is_iterable($post->tags) ? collect($post->tags)->pluck('name')->toArray() : [];

        return Inertia::render('posts/edit', [
            'post' => $post,
            'categories' => $categories,
            'existingTags' => $existingTags,
            'project' => $currentProject->load('collaborators:id,name,email'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $currentProject = $request->attributes->get('current_project');

        // Check if post belongs to current project
        if ($post->project_id !== $currentProject->id) {
            abort(404);
        }

        // Check if user can edit this post
        // Post authors, project editors, admins, and owners can edit
        $canEdit = $post->hasAuthor($request->user()) ||
                   $currentProject->canUserEdit($request->user());

        if (!$canEdit) {
            abort(403);
        }

        // Get all collaborator IDs for validation
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'category_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($currentProject, $userIds) {
                    if ($value && !Category::where('id', $value)
                                          ->whereIn('user_id', $userIds)
                                          ->where('project_id', $currentProject->id)
                                          ->exists()) {
                        $fail('The selected category must belong to the current project.');
                    }
                }
            ],
            'excerpt' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:255',
            'is_draft' => 'nullable|boolean',
            'authors' => 'nullable|array',
            'authors.*' => 'integer|exists:users,id',
        ]);

        $post->update($data);

        // Handle authors
        if ($request->has('authors') && is_array($request->authors)) {
            $authorData = [];
            foreach ($request->authors as $index => $authorId) {
                // Ensure author is part of the project
                if (in_array($authorId, $userIds)) {
                    $authorData[$authorId] = [
                        'contribution_type' => $authorId === $post->user_id ? 'primary' : 'co-author',
                        'order' => $index,
                    ];
                }
            }
            $post->authors()->sync($authorData);
        }

        // Handle tags
        if ($request->has('tags') && is_array($request->tags)) {
            $tagIds = [];
            foreach ($request->tags as $tagName) {
                $tag = \App\Models\Tag::firstOrCreate(['name' => trim($tagName)]);
                $tagIds[] = $tag->id;
            }
            $post->tags()->sync($tagIds);
        } else {
            $post->tags()->detach();
        }

        return redirect()->route('posts');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Post $post)
    {
        $currentProject = $request->attributes->get('current_project');

        // Check if post belongs to current project
        if ($post->project_id !== $currentProject->id) {
            abort(404);
        }

        // Only allow deletion by post owner or project owner/admin
        $canDelete = $post->user_id === $request->user()->id ||
                     $currentProject->canUserAdmin($request->user());

        if (!$canDelete) {
            abort(403);
        }

        $post->delete();
        return redirect()->route('posts');
    }

    /**
     * Handle image uploads from the editor.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120', // max 5MB
        ]);
        $user = $request->user();
        $file = $request->file('file');
        $timestamp = now()->timestamp;
        $filename = $timestamp . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());

        try {
            // store on the public disk (storage/app/public/{userId}/filename)
            $storedPath = Storage::disk('public')->putFileAs((string) $user->id, $file, $filename);

            // generate the public url using the public storage symlink (public/storage)
            // use asset() to avoid static analysis issues with the Filesystem contract
            $url = asset('storage/' . ltrim($storedPath, '/'));

            // Create media record
            $media = \App\Models\Media::create([
                'user_id' => $user->id,
                'name' => $file->getClientOriginalName(),
                'file_name' => $filename,
                'mime_type' => $file->getMimeType(),
                'path' => $storedPath,
                'disk' => 'public',
                'file_hash' => hash_file('sha256', $file->getRealPath()),
                'collection' => 'default',
                'size' => $file->getSize(),
            ]);

            return response()->json(['url' => $url, 'media' => $media]);
        } catch (\Exception $e) {
            logger()->error('Image upload failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Upload failed'], 500);
        }
    }
}
