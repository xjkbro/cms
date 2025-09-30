<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;



class PostAPIController extends Controller
{

    #[OA\Get(
        path: "/posts",
        summary: "Get all posts",
        description: "Retrieve a list of all posts the user has access to across all projects",
        tags: ["Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "category_id",
                in: "query",
                required: false,
                description: "Filter posts by category ID",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "project_id",
                in: "query",
                required: false,
                description: "Filter posts by project ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "data" => new OA\Property(
                            property: "data",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    "id" => new OA\Property(property: "id", type: "integer"),
                                    "title" => new OA\Property(property: "title", type: "string"),
                                    "content" => new OA\Property(property: "content", type: "string"),
                                    "excerpt" => new OA\Property(property: "excerpt", type: "string", nullable: true),
                                    "feature_image_url" => new OA\Property(property: "feature_image_url", type: "string", nullable: true),
                                    "is_draft" => new OA\Property(property: "is_draft", type: "boolean"),
                                    "project_id" => new OA\Property(property: "project_id", type: "integer"),
                                    "category_id" => new OA\Property(property: "category_id", type: "integer", nullable: true),
                                    "user" => new OA\Property(property: "user", type: "object"),
                                    "category" => new OA\Property(property: "category", type: "object", nullable: true)
                                ]
                            )
                        ),
                        "message" => new OA\Property(property: "message", type: "string", example: "Successfully queried posts"),
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $user = $request->user();

        // Get all projects the user has access to (as owner or collaborator)
        $accessibleProjects = \App\Models\Project::where('user_id', $user->id)
            ->orWhereHas('collaborators', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->pluck('id');

        // Build the query
        $query = Post::with('category', 'user', 'project')
            ->whereIn('project_id', $accessibleProjects);

        // Apply optional filters
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $posts = $query->get();

        return response()->json([
            'data' => $posts,
            'message' => 'Successfully queried posts'
        ]);
    }

    #[OA\Get(
        path: "/posts/{id}",
        summary: "Get a specific post",
        description: "Retrieve a specific post by ID",
        tags: ["Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Post ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "data" => new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                "id" => new OA\Property(property: "id", type: "integer"),
                                "title" => new OA\Property(property: "title", type: "string"),
                                "content" => new OA\Property(property: "content", type: "string"),
                                "excerpt" => new OA\Property(property: "excerpt", type: "string", nullable: true),
                                "feature_image_url" => new OA\Property(property: "feature_image_url", type: "string", nullable: true),
                                "is_draft" => new OA\Property(property: "is_draft", type: "boolean"),
                                "project_id" => new OA\Property(property: "project_id", type: "integer"),
                                "category_id" => new OA\Property(property: "category_id", type: "integer", nullable: true),
                                "user" => new OA\Property(property: "user", type: "object"),
                                "category" => new OA\Property(property: "category", type: "object", nullable: true),
                                "project" => new OA\Property(property: "project", type: "object")
                            ]
                        ),
                        "message" => new OA\Property(property: "message", type: "string", example: "Post retrieved successfully")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Post not found")
        ]
    )]
    public function show($id)
    {
        $user = request()->user();

        // Get all projects the user has access to
        $accessibleProjects = \App\Models\Project::where('user_id', $user->id)
            ->orWhereHas('collaborators', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->pluck('id');

        $post = Post::with('category', 'user', 'project')
            ->where('id', $id)
            ->whereIn('project_id', $accessibleProjects)
            ->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json([
            'data' => $post,
            'message' => 'Post retrieved successfully'
        ]);
    }

    #[OA\Post(
        path: "/posts",
        summary: "Create a new post",
        description: "Create a new post",
        tags: ["Posts"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    "title" => new OA\Property(property: "title", type: "string", example: "My New Post"),
                    "content" => new OA\Property(property: "content", type: "string", example: "This is the content of my post"),
                    "category_id" => new OA\Property(property: "category_id", type: "integer", example: 1),
                    "feature_image_url" => new OA\Property(property: "feature_image_url", type: "string", nullable: true, example: "https://example.com/image.jpg")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Post created successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "message" => new OA\Property(property: "message", type: "string", example: "Post created successfully")
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(Request $request)
    {

        return response()->json(['message' => 'Post created successfully']);
    }

    #[OA\Put(
        path: "/posts/{id}",
        summary: "Update a post",
        description: "Update an existing post",
        tags: ["Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Post ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    "title" => new OA\Property(property: "title", type: "string", example: "Updated Post Title"),
                    "content" => new OA\Property(property: "content", type: "string", example: "Updated post content"),
                    "category_id" => new OA\Property(property: "category_id", type: "integer", example: 1),
                    "feature_image_url" => new OA\Property(property: "feature_image_url", type: "string", nullable: true, example: "https://example.com/image.jpg")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Post updated successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "message" => new OA\Property(property: "message", type: "string", example: "Post with ID: 1 updated successfully")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Post not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, $id)
    {
        return response()->json(['message' => "Post with ID: $id updated successfully"]);
    }

    #[OA\Delete(
        path: "/posts/{id}",
        summary: "Delete a post",
        description: "Delete an existing post",
        tags: ["Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Post ID",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Post deleted successfully",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "message" => new OA\Property(property: "message", type: "string", example: "Post with ID: 1 deleted successfully")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Post not found")
        ]
    )]
    public function destroy($id)
    {
        return response()->json(['message' => "Post with ID: $id deleted successfully"]);
    }
}
