<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Get projects owned by user
        $ownedProjects = $user->projects()
            ->withCount(['posts', 'categories'])
            ->get();
            
        // Get projects where user is a collaborator
        $collaboratingProjects = $user->collaboratingProjects()
            ->withCount(['posts', 'categories'])
            ->get();
            
        // Merge and add ownership info
        $ownedProjects = $ownedProjects->map(function ($project) {
            $project->is_owner = true;
            $project->user_role = 'owner';
            return $project;
        });
        
        $collaboratingProjects = $collaboratingProjects->map(function ($project) {
            $project->is_owner = false;
            $project->user_role = $project->pivot->role;
            return $project;
        });
        
        $allProjects = $ownedProjects->concat($collaboratingProjects)
            ->sortByDesc('is_default')
            ->sortByDesc('created_at')
            ->values();

        return Inertia::render('projects/index', [
            'projects' => $allProjects,
        ]);
    }

    public function create()
    {
        return Inertia::render('projects/create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $project = Auth::user()->projects()->create([
            'name' => $request->name,
            'description' => $request->description,
            'slug' => Str::slug($request->name) . '-' . Str::random(6),
            'is_active' => true,
            'is_default' => Auth::user()->projects()->count() === 0, // First project is default
        ]);

        // If requested via the sidebar modal, switch to the new project and redirect to dashboard
        if ($request->has('switch_to_project') && $request->boolean('switch_to_project')) {
            session(['current_project_id' => $project->id]);
            return redirect()->route('dashboard')
                ->with('success', "Project '{$project->name}' created and activated!");
        }

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully!');
    }

    public function edit(Project $project)
    {
        // Ensure user owns the project
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('projects/edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project)
    {
        // Ensure user owns the project
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $project->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('projects.index')
            ->with('success', 'Project updated successfully!');
    }

    public function destroy(Project $project)
    {
        // Ensure user owns the project
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        // Don't allow deleting the last project
        if (Auth::user()->projects()->count() <= 1) {
            return redirect()->route('projects.index')
                ->with('error', 'You must have at least one project.');
        }

        // If this was the default project, make another one default
        if ($project->is_default) {
            $newDefault = Auth::user()->projects()
                ->where('id', '!=', $project->id)
                ->first();
            if ($newDefault) {
                $newDefault->makeDefault();
            }
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully!');
    }

    public function makeDefault(Project $project)
    {
        // Ensure user owns the project
        if ($project->user_id !== Auth::id()) {
            abort(403);
        }

        $project->makeDefault();

        return redirect()->route('projects.index')
            ->with('success', 'Default project updated!');
    }

    public function switch(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($request->project_id);

        // Ensure user has access to the project (owner or collaborator)
        if (!$project->canUserView(Auth::user())) {
            abort(403);
        }

        // Store current project in session
        session(['current_project_id' => $project->id]);

        // Redirect back to where the user was, or dashboard if no referer
        return redirect()->back()->with('success', "Switched to project: {$project->name}");
    }
}
