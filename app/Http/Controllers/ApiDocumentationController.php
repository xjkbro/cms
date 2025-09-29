<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
 *     url=L5_SWAGGER_CONST_HOST,
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
}