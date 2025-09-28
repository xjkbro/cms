<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImprovedMediaController extends Controller
{
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

        // Generate cache key based on file path and dimensions
        $cacheKey = 'resized_image_' . md5($path . '_' . $width . '_' . $height . '_' . $fit . '_' . $media->updated_at);

        // Try to get cached version first
        if (Storage::disk('public')->exists('cache/images/' . $cacheKey . '.jpg')) {
            $response = response()->file(Storage::disk('public')->path('cache/images/' . $cacheKey . '.jpg'));
            $response->headers->set('Cache-Control', 'public, max-age=31536000');
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

            // Encode the image (Intervention Image v3 syntax)
            $encodedImage = $image->encode()->toString();

            // Cache the resized image to disk (not /tmp/)
            $cacheDir = 'cache/images';
            if (!Storage::disk('public')->exists($cacheDir)) {
                Storage::disk('public')->makeDirectory($cacheDir);
            }

            Storage::disk('public')->put($cacheDir . '/' . $cacheKey . '.jpg', $encodedImage);

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

    /**
     * Clean up old cached images (call this via scheduled command)
     */
    public function cleanupCache()
    {
        $cacheDir = 'cache/images';
        if (!Storage::disk('public')->exists($cacheDir)) {
            return;
        }

        $files = Storage::disk('public')->files($cacheDir);
        $cutoff = now()->subDays(30); // Keep cached images for 30 days

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            if ($lastModified < $cutoff->timestamp) {
                Storage::disk('public')->delete($file);
            }
        }
    }
}
