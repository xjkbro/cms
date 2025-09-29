<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
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

        // Get categories from all project collaborators
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        return Inertia::render('categories/categories', [
            'categories' => Category::whereIn('user_id', $userIds)
                ->where('project_id', $currentProject->id)
                ->with('user:id,name')
                ->get(),
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

        return Inertia::render('categories/edit', [
            'category' => null,
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

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Get all collaborator IDs for slug uniqueness check
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        // Generate unique slug within the current project
        $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        $originalSlug = $data['slug'];
        $i = 1;
        while (Category::whereIn('user_id', $userIds)
                      ->where('project_id', $currentProject->id)
                      ->where('slug', $data['slug'])
                      ->exists()) {
            $data['slug'] = $originalSlug . '-' . $i++;
        }

        $data['user_id'] = $request->user()->id;
        $data['project_id'] = $currentProject->id;

        Category::create($data);

        return redirect()->route('categories');
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Category $category)
    {
        $currentProject = $request->attributes->get('current_project');

        // Ensure the category belongs to the current project
        if ($category->project_id !== $currentProject->id) {
            abort(404);
        }

        // Check if user can edit categories in this project
        if (!$currentProject->canUserEdit($request->user())) {
            abort(403);
        }

        // Ensure the category belongs to a project collaborator
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        if (!in_array($category->user_id, $userIds)) {
            abort(404);
        }

        return Inertia::render('categories/edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
    {
        $currentProject = $request->attributes->get('current_project');

        // Ensure the category belongs to the current project
        if ($category->project_id !== $currentProject->id) {
            abort(404);
        }

        // Check if user can edit categories in this project
        if (!$currentProject->canUserEdit($request->user())) {
            abort(403);
        }

        // Ensure the category belongs to a project collaborator
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        if (!in_array($category->user_id, $userIds)) {
            abort(404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Generate unique slug within project (excluding current category)
        $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
        $originalSlug = $data['slug'];
        $i = 1;
        while (Category::whereIn('user_id', $userIds)
                      ->where('project_id', $currentProject->id)
                      ->where('slug', $data['slug'])
                      ->where('id', '!=', $category->id)
                      ->exists()) {
            $data['slug'] = $originalSlug . '-' . $i++;
        }

        $category->update($data);

        return redirect()->route('categories');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category)
    {
        $currentProject = $request->attributes->get('current_project');

        // Ensure the category belongs to the current project
        if ($category->project_id !== $currentProject->id) {
            abort(404);
        }

        // Check if user can edit categories in this project
        if (!$currentProject->canUserEdit($request->user())) {
            abort(403);
        }

        // Ensure the category belongs to a project collaborator
        $collaboratorIds = $currentProject->collaborators()->pluck('users.id')->toArray();
        $userIds = array_merge([$currentProject->user_id], $collaboratorIds);

        if (!in_array($category->user_id, $userIds)) {
            abort(404);
        }

        $category->delete();
        return redirect()->route('categories');
    }
}
