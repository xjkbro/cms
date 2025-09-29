<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ProjectAPIController extends Controller
{
    #[OA\Get(
        path: "/api/projects",
        summary: "Get all projects for authenticated user",
        description: "Retrieve all projects belonging to the authenticated user",
        security: [["sanctum" => []]],
        tags: ["Projects"],
        parameters: [
            new OA\Parameter(
                name: "include_stats",
                description: "Include post and category counts",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean", example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Projects retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "data" => new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/Project")
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized")
        ]
    )]
    public function index(Request $request)
    {
        $query = Auth::user()->projects();

        if ($request->boolean('include_stats')) {
            $query->withCount(['posts', 'categories']);
        }

        $projects = $query->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    #[OA\Get(
        path: "/api/projects/{id}",
        summary: "Get a specific project",
        description: "Retrieve a specific project by ID with detailed information",
        security: [["sanctum" => []]],
        tags: ["Projects"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Project ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "include_posts",
                description: "Include posts in the response",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean", example: false)
            ),
            new OA\Parameter(
                name: "include_categories",
                description: "Include categories in the response",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "boolean", example: false)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Project retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "data" => new OA\Property(property: "data", ref: "#/components/schemas/Project")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Project not found")
        ]
    )]
    public function show(Request $request, $id)
    {
        $query = Project::where('id', $id)->where('user_id', Auth::id());

        $with = [];
        if ($request->boolean('include_posts')) {
            $with[] = 'posts';
        }
        if ($request->boolean('include_categories')) {
            $with[] = 'categories';
        }

        if (!empty($with)) {
            $query->with($with);
        }

        $project = $query->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $project
        ]);
    }

    #[OA\Post(
        path: "/api/projects",
        summary: "Create a new project",
        description: "Create a new project for the authenticated user",
        security: [["sanctum" => []]],
        tags: ["Projects"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name"],
                properties: [
                    "name" => new OA\Property(property: "name", type: "string", maxLength: 255, example: "My New Project"),
                    "description" => new OA\Property(property: "description", type: "string", maxLength: 1000, nullable: true, example: "A description of my project"),
                    "is_active" => new OA\Property(property: "is_active", type: "boolean", example: true, description: "Whether the project is active"),
                    "make_default" => new OA\Property(property: "make_default", type: "boolean", example: false, description: "Make this project the default")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Project created successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "message" => new OA\Property(property: "message", type: "string", example: "Project created successfully"),
                        "data" => new OA\Property(property: "data", ref: "#/components/schemas/Project")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'make_default' => 'boolean',
        ]);

        $isFirstProject = Auth::user()->projects()->count() === 0;

        $project = Auth::user()->projects()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'slug' => Str::slug($data['name']) . '-' . Str::random(6),
            'is_active' => $data['is_active'] ?? true,
            'is_default' => $isFirstProject || ($data['make_default'] ?? false),
        ]);

        // If this should be the default project, make it so
        if (!$isFirstProject && ($data['make_default'] ?? false)) {
            $project->makeDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => $project
        ], 201);
    }

    #[OA\Put(
        path: "/api/projects/{id}",
        summary: "Update a project",
        description: "Update an existing project",
        security: [["sanctum" => []]],
        tags: ["Projects"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Project ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name"],
                properties: [
                    "name" => new OA\Property(property: "name", type: "string", maxLength: 255, example: "Updated Project Name"),
                    "description" => new OA\Property(property: "description", type: "string", maxLength: 1000, nullable: true, example: "Updated description"),
                    "is_active" => new OA\Property(property: "is_active", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Project updated successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "message" => new OA\Property(property: "message", type: "string", example: "Project updated successfully"),
                        "data" => new OA\Property(property: "data", ref: "#/components/schemas/Project")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Project not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $project = Project::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $project->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $project->description,
            'is_active' => $data['is_active'] ?? $project->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => $project
        ]);
    }

    #[OA\Delete(
        path: "/api/projects/{id}",
        summary: "Delete a project",
        description: "Delete an existing project (cannot delete if it's the only project)",
        security: [["sanctum" => []]],
        tags: ["Projects"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Project ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Project deleted successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "message" => new OA\Property(property: "message", type: "string", example: "Project deleted successfully")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Cannot delete the only project"),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Project not found")
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $project = Project::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }

        // Don't allow deleting the last project
        if (Auth::user()->projects()->count() <= 1) {
            return response()->json([
                'success' => false,
                'message' => 'You must have at least one project'
            ], 400);
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

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully'
        ]);
    }

    #[OA\Post(
        path: "/api/projects/{id}/make-default",
        summary: "Make a project the default",
        description: "Set a project as the default project for the user",
        security: [["sanctum" => []]],
        tags: ["Projects"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Project ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Project set as default successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "message" => new OA\Property(property: "message", type: "string", example: "Project set as default successfully"),
                        "data" => new OA\Property(property: "data", ref: "#/components/schemas/Project")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Project not found")
        ]
    )]
    public function makeDefault(Request $request, $id)
    {
        $project = Project::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }

        $project->makeDefault();

        return response()->json([
            'success' => true,
            'message' => 'Project set as default successfully',
            'data' => $project
        ]);
    }

    #[OA\Post(
        path: "/api/projects/switch",
        summary: "Switch current project",
        description: "Switch the current active project for API operations",
        security: [["sanctum" => []]],
        tags: ["Projects"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["project_id"],
                properties: [
                    "project_id" => new OA\Property(property: "project_id", type: "integer", example: 1, description: "ID of the project to switch to")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Project switched successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "message" => new OA\Property(property: "message", type: "string", example: "Switched to project: My Project"),
                        "data" => new OA\Property(property: "data", ref: "#/components/schemas/Project")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Project access denied"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function switch(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        $project = Project::findOrFail($request->project_id);

        // Ensure user owns the project
        if ($project->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Project access denied'
            ], 403);
        }

        // Store current project in session
        session(['current_project_id' => $project->id]);

        return response()->json([
            'success' => true,
            'message' => "Switched to project: {$project->name}",
            'data' => $project
        ]);
    }
}

#[OA\Schema(
    schema: "Project",
    type: "object",
    properties: [
        "id" => new OA\Property(property: "id", type: "integer", example: 1),
        "user_id" => new OA\Property(property: "user_id", type: "integer", example: 1),
        "name" => new OA\Property(property: "name", type: "string", example: "My Project"),
        "slug" => new OA\Property(property: "slug", type: "string", example: "my-project-abc123"),
        "description" => new OA\Property(property: "description", type: "string", nullable: true, example: "A description of my project"),
        "is_active" => new OA\Property(property: "is_active", type: "boolean", example: true),
        "is_default" => new OA\Property(property: "is_default", type: "boolean", example: false),
        "created_at" => new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00Z"),
        "updated_at" => new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00Z"),
        "posts_count" => new OA\Property(property: "posts_count", type: "integer", example: 5, nullable: true, description: "Number of posts (when include_stats=true)"),
        "categories_count" => new OA\Property(property: "categories_count", type: "integer", example: 3, nullable: true, description: "Number of categories (when include_stats=true)"),
        "posts" => new OA\Property(
            property: "posts",
            type: "array",
            items: new OA\Items(type: "object"),
            nullable: true,
            description: "Posts (when include_posts=true)"
        ),
        "categories" => new OA\Property(
            property: "categories",
            type: "array",
            items: new OA\Items(type: "object"),
            nullable: true,
            description: "Categories (when include_categories=true)"
        )
    ]
)]
class ProjectSchema {}
