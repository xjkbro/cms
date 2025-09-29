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
    #[OA\Post(
        path: "/auth/login",
        summary: "Login and get API token",
        description: "Authenticate user and receive Bearer token for API access",
        tags: ["Authentication"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                required: ["email", "password"],
                properties: [
                    "email" => new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                    "password" => new OA\Property(property: "password", type: "string", format: "password", example: "password")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "token" => new OA\Property(property: "token", type: "string", example: "1|abc123def456..."),
                        "user" => new OA\Property(
                            property: "user",
                            type: "object",
                            properties: [
                                "id" => new OA\Property(property: "id", type: "integer", example: 1),
                                "name" => new OA\Property(property: "name", type: "string", example: "John Doe"),
                                "email" => new OA\Property(property: "email", type: "string", example: "user@example.com")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 422, description: "Invalid credentials")
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    #[OA\Post(
        path: "/auth/logout",
        summary: "Logout and revoke token",
        description: "Revoke the current API token",
        tags: ["Authentication"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logout successful",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "message" => new OA\Property(property: "message", type: "string", example: "Logged out successfully")
                    ]
                )
            )
        ]
    )]
    public function logout(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No authenticated user'], 401);
        }

        // Try common token retrieval methods (Sanctum: currentAccessToken, Passport: token)
        $token = null;
        if (method_exists($user, 'currentAccessToken')) {
            $token = $user->currentAccessToken();
        } elseif (method_exists($user, 'token')) {
            $token = $user->token();
        }

        if ($token) {
            // Prefer delete() (used by Sanctum/personal access tokens), fall back to revoke() (Passport)
            if (method_exists($token, 'delete')) {
                $token->delete();
            } elseif (method_exists($token, 'revoke')) {
                $token->revoke();
            }
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    #[OA\Get(
        path: "/posts",
        summary: "Get all posts",
        description: "Retrieve a list of all posts",
        tags: ["Posts"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Success",
                content: new OA\JsonContent(
                    type: "object",
                    properties: [
                        "message" => new OA\Property(property: "message", type: "string", example: "Posts retrieved successfully"),
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $user = $request->user();
        $posts = Post::with('category', 'user')->where('user_id', $user->id)->get();
        return response()->json(['data' => $posts, 'message' => 'Successfully queried posts']);
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
                        "message" => new OA\Property(property: "message", type: "string", example: "Showing post with ID: 1")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Post not found")
        ]
    )]
    public function show($id)
    {
        return response()->json(['message' => "Showing post with ID: $id"]);
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
                    "category_id" => new OA\Property(property: "category_id", type: "integer", example: 1)
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
                    "category_id" => new OA\Property(property: "category_id", type: "integer", example: 1)
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
