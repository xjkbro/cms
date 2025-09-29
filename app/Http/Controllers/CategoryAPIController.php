<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class CategoryAPIController extends Controller
{
    #[OA\Get(
        path: "/categories",
        summary: "Get all categories for current project",
        description: "Retrieve all categories belonging to the authenticated user's current project",
        security: [["sanctum" => []]],
        tags: ["Categories"],
        parameters: [
            new OA\Parameter(
                name: "project_id",
                description: "Project ID to filter categories (optional, uses current project if not provided)",
                in: "query",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categories retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "data" => new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/Category")
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 403, description: "Forbidden")
        ]
    )]
    public function index(Request $request)
    {
        $projectId = $request->get('project_id') ?? session('current_project_id');

        if (!$projectId) {
            // Get user's default project
            $project = Auth::user()->projects()->where('is_default', true)->first();
            $projectId = $project?->id;
        }

        if (!$projectId) {
            return response()->json([
                'success' => false,
                'message' => 'No project found'
            ], 400);
        }

        // Verify project belongs to user
        $project = Project::where('id', $projectId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found or access denied'
            ], 403);
        }

        $categories = Category::where('user_id', Auth::id())
            ->where('project_id', $projectId)
            ->with('project')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    #[OA\Get(
        path: "/categories/{id}",
        summary: "Get a specific category",
        description: "Retrieve a specific category by ID",
        security: [["sanctum" => []]],
        tags: ["Categories"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Category ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Category retrieved successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "data" => new OA\Property(property: "data", ref: "#/components/schemas/Category")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    public function show(Request $request, $id)
    {
        $projectId = session('current_project_id') ?? Auth::user()->projects()->where('is_default', true)->first()?->id;

        $category = Category::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('project_id', $projectId)
            ->with('project', 'posts')
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    #[OA\Post(
        path: "/categories",
        summary: "Create a new category",
        description: "Create a new category in the current project",
        security: [["sanctum" => []]],
        tags: ["Categories"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["name"],
                properties: [
                    "name" => new OA\Property(property: "name", type: "string", maxLength: 255, example: "Technology"),
                    "description" => new OA\Property(property: "description", type: "string", nullable: true, example: "Articles about technology"),
                    "project_id" => new OA\Property(property: "project_id", type: "integer", nullable: true, example: 1, description: "Project ID (optional, uses current project if not provided)")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Category created successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "message" => new OA\Property(property: "message", type: "string", example: "Category created successfully"),
                        "data" => new OA\Property(property: "data", ref: "#/components/schemas/Category")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {
        $projectId = $request->get('project_id') ?? session('current_project_id') ?? Auth::user()->projects()->where('is_default', true)->first()?->id;

        if (!$projectId) {
            return response()->json([
                'success' => false,
                'message' => 'No project specified'
            ], 400);
        }

        // Verify project belongs to user
        $project = Project::where('id', $projectId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found or access denied'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Generate unique slug for user within the current project
        $data['slug'] = Str::slug($data['name']);
        $originalSlug = $data['slug'];
        $i = 1;
        while (Category::where('user_id', Auth::id())
                      ->where('project_id', $projectId)
                      ->where('slug', $data['slug'])
                      ->exists()) {
            $data['slug'] = $originalSlug . '-' . $i++;
        }

        $data['user_id'] = Auth::id();
        $data['project_id'] = $projectId;

        $category = Category::create($data);
        $category->load('project');

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    #[OA\Put(
        path: "/categories/{id}",
        summary: "Update a category",
        description: "Update an existing category",
        security: [["sanctum" => []]],
        tags: ["Categories"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Category ID",
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
                    "name" => new OA\Property(property: "name", type: "string", maxLength: 255, example: "Updated Technology"),
                    "description" => new OA\Property(property: "description", type: "string", nullable: true, example: "Updated description")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Category updated successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "message" => new OA\Property(property: "message", type: "string", example: "Category updated successfully"),
                        "data" => new OA\Property(property: "data", ref: "#/components/schemas/Category")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Category not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        $projectId = session('current_project_id') ?? Auth::user()->projects()->where('is_default', true)->first()?->id;

        $category = Category::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('project_id', $projectId)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Generate unique slug for user within project (excluding current category)
        $data['slug'] = Str::slug($data['name']);
        $originalSlug = $data['slug'];
        $i = 1;
        while (Category::where('user_id', Auth::id())
                      ->where('project_id', $projectId)
                      ->where('slug', $data['slug'])
                      ->where('id', '!=', $category->id)
                      ->exists()) {
            $data['slug'] = $originalSlug . '-' . $i++;
        }

        $category->update($data);
        $category->load('project');

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    #[OA\Delete(
        path: "/categories/{id}",
        summary: "Delete a category",
        description: "Delete an existing category",
        security: [["sanctum" => []]],
        tags: ["Categories"],
        parameters: [
            new OA\Parameter(
                name: "id",
                description: "Category ID",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Category deleted successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "success" => new OA\Property(property: "success", type: "boolean", example: true),
                        "message" => new OA\Property(property: "message", type: "string", example: "Category deleted successfully")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Category not found")
        ]
    )]
    public function destroy(Request $request, $id)
    {
        $projectId = session('current_project_id') ?? Auth::user()->projects()->where('is_default', true)->first()?->id;

        $category = Category::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('project_id', $projectId)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }
}

#[OA\Schema(
    schema: "Category",
    type: "object",
    properties: [
        "id" => new OA\Property(property: "id", type: "integer", example: 1),
        "user_id" => new OA\Property(property: "user_id", type: "integer", example: 1),
        "project_id" => new OA\Property(property: "project_id", type: "integer", example: 1),
        "name" => new OA\Property(property: "name", type: "string", example: "Technology"),
        "slug" => new OA\Property(property: "slug", type: "string", example: "technology"),
        "description" => new OA\Property(property: "description", type: "string", nullable: true, example: "Articles about technology"),
        "created_at" => new OA\Property(property: "created_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00Z"),
        "updated_at" => new OA\Property(property: "updated_at", type: "string", format: "date-time", example: "2023-01-01T00:00:00Z"),
        "project" => new OA\Property(property: "project", ref: "#/components/schemas/Project", nullable: true),
        "posts" => new OA\Property(
            property: "posts",
            type: "array",
            items: new OA\Items(type: "object"),
            nullable: true
        )
    ]
)]
class CategorySchema {}
