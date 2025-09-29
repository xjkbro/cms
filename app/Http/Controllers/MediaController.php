<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaController extends Controller
{
    /**
     * Get all project IDs the user has access to (owned + collaborated)
     */
    private function getAccessibleProjectIds($user): array
    {
        $ownedProjectIds = $user->projects()->pluck('id')->toArray();
        $collaboratedProjectIds = $user->collaboratingProjects()->pluck('projects.id')->toArray();
        return array_merge($ownedProjectIds, $collaboratedProjectIds);
    }
    /**
     * @OA\Get(
     *     path="/api/media",
     *     operationId="getMedia",
     *     tags={"Media"},
     *     summary="Get media files",
     *     description="Retrieve paginated media files from all accessible projects",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media files retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="media", type="object",
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="file_name", type="string"),
     *                         @OA\Property(property="mime_type", type="string"),
     *                         @OA\Property(property="size", type="integer"),
     *                         @OA\Property(property="url", type="string"),
     *                         @OA\Property(property="project", type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string")
     *                         ),
     *                         @OA\Property(property="user", type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="links", type="object"),
     *                 @OA\Property(property="meta", type="object")
     *             )
     *         )
     *     )
     * )
     * 
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $accessibleProjectIds = $this->getAccessibleProjectIds($user);

        $media = Media::whereIn('project_id', $accessibleProjectIds)
            ->with(['user:id,name', 'project:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if (request()->wantsJson()) {
            return response()->json(['media' => $media]);
        }

        return Inertia::render('media/index', [
            'media' => $media,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/media",
     *     operationId="uploadMedia",
     *     tags={"Media"},
     *     summary="Upload media file",
     *     description="Upload a file or add media via URL to the current project",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary", description="Media file to upload"),
     *                 @OA\Property(property="url", type="string", format="url", description="External URL for media")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Media uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="media", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="file_name", type="string"),
     *                 @OA\Property(property="mime_type", type="string"),
     *                 @OA\Property(property="size", type="integer"),
     *                 @OA\Property(property="project_id", type="integer")
     *             ),
     *             @OA\Property(property="url", type="string", description="Public URL of the uploaded media")
     *         )
     *     ),
     *     @OA\Response(response=400, description="No file or URL provided"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Upload failed")
     * )
     * 
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'required|file|max:5120', // max 5MB
            ]);

            $file = $request->file('file');
            $timestamp = now()->timestamp;
            $filename = $timestamp . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());

            try {
                // store on the public disk (storage/app/public/{userId}/filename)
                $storedPath = Storage::disk('public')->putFileAs((string) $user->id, $file, $filename);

                // generate the public url using the public disk
                $url = asset(Storage::url($storedPath));

                $currentProject = $request->attributes->get('current_project');

                $media = Media::create([
                    'user_id' => $user->id,
                    'project_id' => $currentProject ? $currentProject->id : null,
                    'name' => $file->getClientOriginalName(),
                    'file_name' => $filename,
                    'mime_type' => $file->getMimeType(),
                    'path' => $storedPath,
                    'disk' => 'public',
                    'file_hash' => hash_file('sha256', $file->getRealPath()),
                    'collection' => 'default',
                    'size' => $file->getSize(),
                ]);

                return response()->json([
                    'media' => $media,
                    'url' => $url,
                ]);
            } catch (\Exception $e) {
                logger()->error('Media upload failed', ['error' => $e->getMessage()]);
                return response()->json(['message' => 'Upload failed'], 500);
            }
        } elseif ($request->has('url')) {
            $request->validate([
                'url' => 'required|url',
            ]);

            $url = $request->url;

            // For external URLs, we'll store them directly
            $currentProject = $request->attributes->get('current_project');

            $media = Media::create([
                'user_id' => $user->id,
                'project_id' => $currentProject ? $currentProject->id : null,
                'name' => basename(parse_url($url, PHP_URL_PATH)) ?: 'External Image',
                'file_name' => basename(parse_url($url, PHP_URL_PATH)) ?: 'external',
                'mime_type' => 'image/url', // Custom mime type for external URLs
                'path' => $url,
                'disk' => 'url',
                'file_hash' => hash('sha256', $url),
                'collection' => 'default',
                'size' => 0, // Unknown size for external URLs
            ]);

            return response()->json([
                'media' => $media,
                'url' => $url,
            ]);
        }

        return response()->json(['message' => 'No file or URL provided'], 400);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $accessibleProjectIds = $this->getAccessibleProjectIds($user);

        $media = Media::whereIn('project_id', $accessibleProjectIds)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $media->update([
            'name' => $request->name,
        ]);

        return response()->json(['media' => $media]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $accessibleProjectIds = $this->getAccessibleProjectIds($user);

        $media = Media::whereIn('project_id', $accessibleProjectIds)->findOrFail($id);

        // Only allow deletion if user has edit access to the project
        if (!$media->project->canUserEdit($user)) {
            abort(403);
        }

        // Delete the file from storage if it's not a URL
        if ($media->disk !== 'url') {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();

        return response()->json(['message' => 'Media deleted successfully']);
    }

    /**
     * Serve a resized version of the media image.
     */
    public function image(Request $request, Media $media)
    {
        $user = $request->user();
        
        // Check if user has access to this media through project collaboration
        if ($user) {
            $accessibleProjectIds = $this->getAccessibleProjectIds($user);
            
            if (!in_array($media->project_id, $accessibleProjectIds)) {
                abort(403);
            }
        } else {
            abort(403);
        }

        // Only process images
        if (!str_starts_with($media->mime_type, 'image/')) {
            return redirect($media->url);
        }

        // For URL-based media, redirect to the original URL
        if ($media->disk === 'url') {
            return redirect($media->path);
        }

        $width = $request->query('w');
        $height = $request->query('h');
        $fit = $request->query('fit', 'contain'); // contain, cover, fill, scale-down

        // If no resize parameters, serve original
        if (!$width && !$height) {
            return response()->file(Storage::disk($media->disk)->path($media->path));
        }

        // Validate parameters
        $width = $width ? (int) $width : null;
        $height = $height ? (int) $height : null;

        if (($width && $width <= 0) || ($height && $height <= 0) || ($width && $width > 5000) || ($height && $height > 5000)) {
            abort(400, 'Invalid dimensions');
        }

        try {
            $imagePath = Storage::disk($media->disk)->path($media->path);

            // TEMP: Just return original image to test UI
            // Uncomment below for actual resizing

            $manager = new ImageManager(new Driver());
            $image = $manager->read($imagePath);
            if ($width) {
                $image->scale(width: $width);
            }
            return response($image->encode()->toString())
                ->header('Content-Type', 'image/jpeg');

        } catch (\Exception $e) {
            // Log the error
            Log::error('Image resize failed: ' . $e->getMessage());
            // If resizing fails, serve original
            return response()->file(Storage::disk($media->disk)->path($media->path));
        }
    }

    /**
     * Serve storage files with optional resizing and caching.
     */
    public function serveResizedStorage(Request $request, string $path)
    {
        $width = $request->query('w');
        $height = $request->query('h');
        $fit = $request->query('fit', 'contain');

        // If no resize parameters, serve the file normally
        if (!$width && !$height) {
            $fullPath = storage_path('app/public/' . $path);
            if (file_exists($fullPath)) {
                return response()->file($fullPath);
            }
            abort(404);
        }

        // Find the media record by path
        $media = Media::where('path', $path)->where('disk', 'public')->first();

        if (!$media) {
            // If no media record found, try to serve the file anyway
            $fullPath = storage_path('app/public/' . $path);
            if (file_exists($fullPath)) {
                return response()->file($fullPath);
            }
            abort(404);
        }

        // Only process images
        if (!str_starts_with($media->mime_type, 'image/')) {
            return response()->file(storage_path('app/public/' . $path));
        }

        // Validate parameters
        $width = $width ? (int) $width : null;
        $height = $height ? (int) $height : null;

        if (($width && $width <= 0) || ($height && $height <= 0) || ($width && $width > 5000) || ($height && $height > 5000)) {
            abort(400, 'Invalid dimensions');
        }

        // Generate cache key based on file path, dimensions, and file modification time
        $cacheKey = md5($path . '_' . $width . '_' . $height . '_' . $fit . '_' . ($media->updated_at ?? ''));
        $cacheDir = 'cache/images';
        $cachedPath = $cacheDir . '/' . $cacheKey . '.jpg';

        // Check if cached version exists
        if (Storage::disk('public')->exists($cachedPath)) {
            $response = response()->file(Storage::disk('public')->path($cachedPath));
            $response->headers->set('Cache-Control', 'public, max-age=31536000'); // 1 year cache
            return $response;
        }

        try {
            $imagePath = storage_path('app/public/' . $path);

            // Create Intervention Image instance and resize
            $manager = new ImageManager(new Driver());
            $image = $manager->read($imagePath);

            // Resize based on parameters
            if ($width && $height) {
                switch ($fit) {
                    case 'cover':
                        $image->cover($width, $height);
                        break;
                    case 'contain':
                        $image->contain($width, $height);
                        break;
                    case 'fill':
                        $image->resize($width, $height);
                        break;
                    default:
                        $image->contain($width, $height);
                }
            } elseif ($width) {
                $image->scale(width: $width);
            } elseif ($height) {
                $image->scale(height: $height);
            }

            // Encode the image
            $encodedImage = $image->encode()->toString();

            // Ensure cache directory exists
            if (!Storage::disk('public')->exists($cacheDir)) {
                Storage::disk('public')->makeDirectory($cacheDir);
            }

            // Save to cache for future requests
            Storage::disk('public')->put($cachedPath, $encodedImage);

            // Return the resized image
            return response($encodedImage)
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=31536000'); // 1 year cache

        } catch (\Exception $e) {
            Log::error('Image resize failed: ' . $e->getMessage());
            // If resizing fails, serve original
            return response()->file(storage_path('app/public/' . $path));
        }
    }
}
