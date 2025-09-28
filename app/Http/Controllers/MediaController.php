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
     * Display a listing of the resource.
     */
    public function index()
    {
        $media = Media::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if (request()->wantsJson()) {
            return response()->json(['media' => $media]);
        }

        return Inertia::render('Media/Index', [
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

                $media = Media::create([
                    'user_id' => $user->id,
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
            $media = Media::create([
                'user_id' => $user->id,
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
        $media = Media::where('user_id', Auth::id())->findOrFail($id);

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
    public function destroy(string $id)
    {
        $media = Media::where('user_id', Auth::id())->findOrFail($id);

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
        // Check if user owns this media
        if ($media->user_id !== Auth::id()) {
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
     * Serve storage files with optional resizing.
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

        // For storage URL resizing, we allow public access since these are meant to be publicly viewable
        // Skip authentication check for public storage access
        // if ($media && $media->user_id !== Auth::id()) {
        //     abort(403);
        // }

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

        try {
            $imagePath = storage_path('app/public/' . $path);

            // Create Intervention Image instance and resize
            $manager = new ImageManager(new Driver());
            $image = $manager->read($imagePath);

            // Resize based on parameters
            if ($width && $height) {
                $image->cover($width, $height);
            } elseif ($width) {
                $image->scale(width: $width);
            } elseif ($height) {
                $image->scale(height: $height);
            }

            // Return the resized image
            return response($image->encode()->toString())
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=86400');

        } catch (\Exception $e) {
            Log::error('Image resize failed: ' . $e->getMessage());
            // If resizing fails, serve original
            return response()->file(storage_path('app/public/' . $path));
        }
    }
}
