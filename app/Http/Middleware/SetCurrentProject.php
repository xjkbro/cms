<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentProject
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Get current project from session or default
            $currentProjectId = session('current_project_id');
            $currentProject = null;

            if ($currentProjectId) {
                // Check both owned and collaborating projects
                $currentProject = $user->projects()->find($currentProjectId);
                if (!$currentProject) {
                    $currentProject = $user->collaboratingProjects()->find($currentProjectId);
                }
            }

            // If no current project or invalid project, get the default one
            if (!$currentProject) {
                $currentProject = Project::defaultForUser($user->id);
            }

            // If still no project, get the first one (owned or collaborating) or create a default one
            if (!$currentProject) {
                $currentProject = $user->projects()->first();
                if (!$currentProject) {
                    $currentProject = $user->collaboratingProjects()->first();
                }

                if (!$currentProject) {
                    // Create a default project for the user
                    $currentProject = $user->projects()->create([
                        'name' => 'My First Project',
                        'description' => 'Default project created automatically',
                        'slug' => 'my-first-project-' . \Illuminate\Support\Str::random(6),
                        'is_active' => true,
                        'is_default' => true,
                    ]);
                }
            }

            // Update session
            session(['current_project_id' => $currentProject->id]);

            // Make current project available to all views and requests
            $request->attributes->set('current_project', $currentProject);
            View::share('currentProject', $currentProject);

            // Also get all user projects for the project switcher (owned + collaborating)
            $ownedProjects = $user->projects()->where('is_active', true)->get();
            $collaboratingProjects = $user->collaboratingProjects()->where('is_active', true)->get();
            $allProjects = $ownedProjects->concat($collaboratingProjects);
            View::share('userProjects', $allProjects);
        }

        return $next($request);
    }
}
