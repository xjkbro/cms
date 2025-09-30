<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="CMS API Documentation",
 *     description="A comprehensive Content Management System with project collaboration, multi-author posts, and media management",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Laravel Sanctum token authentication"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and registration endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Projects",
 *     description="Project management and collaboration endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Posts",
 *     description="Blog posts and content management endpoints with multi-author support"
 * )
 *
 * @OA\Tag(
 *     name="Categories",
 *     description="Content categorization endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Media",
 *     description="File upload and media management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Collaboration",
 *     description="Project collaboration and team management endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Analytics",
 *     description="Page views and content analytics endpoints"
 * )
 */
class ApiDocumentationController extends Controller
{
    // This controller exists solely for API documentation annotations

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
}
