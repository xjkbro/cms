<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use App\Mail\ProjectInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class ProjectCollaborationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/projects/{project}/collaboration",
     *     operationId="getProjectCollaboration",
     *     tags={"Collaboration"},
     *     summary="Get project collaboration details",
     *     description="Retrieve collaborators and pending invitations for a project",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Collaboration details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="project", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="collaborators", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="pivot", type="object",
     *                         @OA\Property(property="role", type="string", enum={"admin", "editor", "viewer"}),
     *                         @OA\Property(property="joined_at", type="string", format="date-time")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="pendingInvitations", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="role", type="string"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - User cannot admin this project"),
     *     @OA\Response(response=404, description="Project not found")
     * )
     */
    public function index(Request $request, Project $project)
    {
        // Check if user can admin the project
        if (!$project->canUserAdmin($request->user())) {
            abort(403);
        }

        $collaborators = $project->collaborators()
            ->select(['users.id', 'users.name', 'users.email'])
            ->withPivot(['role', 'joined_at'])
            ->get();

        $pendingInvitations = $project->invitations()
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->with('invitedBy:id,name')
            ->get();

        return Inertia::render('projects/collaboration', [
            'project' => $project->load('user:id,name'),
            'collaborators' => $collaborators,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/projects/{project}/collaboration/invite",
     *     operationId="inviteToProject",
     *     tags={"Collaboration"},
     *     summary="Invite user to project",
     *     description="Send an email invitation to collaborate on a project",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         description="Project ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "role"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="role", type="string", enum={"admin", "editor", "viewer"}, example="editor")
     *         )
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Invitation sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invitation sent successfully!")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Forbidden - User cannot admin this project"),
     *     @OA\Response(response=422, description="Validation error - User already collaborator or pending invitation exists")
     * )
     */
    public function invite(Request $request, Project $project)
    {
        if (!$project->canUserAdmin($request->user())) {
            abort(403);
        }

        $request->validate([
            'email' => 'required|email|different:' . $request->user()->email,
            'role' => ['required', Rule::in(['admin', 'editor', 'viewer'])],
        ]);

        // Check if user is already a collaborator
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser && $project->hasUser($existingUser)) {
            return back()->withErrors(['email' => 'User is already a collaborator on this project.']);
        }

        // Check if there's already a pending invitation
        $existingInvitation = ProjectInvitation::where('project_id', $project->id)
            ->where('email', $request->email)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            return back()->withErrors(['email' => 'An invitation is already pending for this email.']);
        }

        // Create invitation
        $invitation = ProjectInvitation::create([
            'project_id' => $project->id,
            'invited_by' => $request->user()->id,
            'email' => $request->email,
            'role' => $request->role,
        ]);

        // Load relationships for email template
        $invitation->load(['project', 'invitedBy']);

        // Send invitation email
        Mail::to($request->email)->send(new ProjectInvitationMail($invitation));

        return back()->with('success', 'Invitation sent successfully!');
    }

    public function acceptInvitation(Request $request, string $token)
    {
        $invitation = ProjectInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isPending()) {
            return redirect()->route('dashboard')->withErrors(['invitation' => 'This invitation is no longer valid.']);
        }

        if ($invitation->email !== $request->user()->email) {
            return redirect()->route('dashboard')->withErrors(['invitation' => 'This invitation was not sent to your email address.']);
        }

        $invitation->accept($request->user());

        return redirect()->route('dashboard')->with('success', 'You have successfully joined the project!');
    }

    public function declineInvitation(Request $request, string $token)
    {
        $invitation = ProjectInvitation::where('token', $token)->firstOrFail();

        if (!$invitation->isPending()) {
            return redirect()->route('dashboard')->withErrors(['invitation' => 'This invitation is no longer valid.']);
        }

        $invitation->decline();

        return redirect()->route('dashboard')->with('success', 'Invitation declined.');
    }

    public function updateRole(Request $request, Project $project, User $user)
    {
        if (!$project->canUserAdmin($request->user())) {
            abort(403);
        }

        // Can't change owner role
        if ($project->user_id === $user->id) {
            return back()->withErrors(['role' => 'Cannot change the owner\'s role.']);
        }

        $request->validate([
            'role' => ['required', Rule::in(['admin', 'editor', 'viewer'])],
        ]);

        $project->collaborators()->updateExistingPivot($user->id, ['role' => $request->role]);

        return back()->with('success', 'User role updated successfully!');
    }

    public function removeCollaborator(Request $request, Project $project, User $user)
    {
        if (!$project->canUserAdmin($request->user())) {
            abort(403);
        }

        // Can't remove owner
        if ($project->user_id === $user->id) {
            return back()->withErrors(['user' => 'Cannot remove the project owner.']);
        }

        $project->collaborators()->detach($user->id);

        return back()->with('success', 'Collaborator removed successfully!');
    }

    public function cancelInvitation(Request $request, Project $project, ProjectInvitation $invitation)
    {
        if (!$project->canUserAdmin($request->user())) {
            abort(403);
        }

        if ($invitation->project_id !== $project->id) {
            abort(404);
        }

        $invitation->update(['status' => 'expired']);

        return back()->with('success', 'Invitation cancelled successfully!');
    }
}
