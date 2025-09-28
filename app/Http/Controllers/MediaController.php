<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $media = Media::where('user_id', auth()->id())
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
        $media = Media::where('user_id', auth()->id())->findOrFail($id);

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
        $media = Media::where('user_id', auth()->id())->findOrFail($id);

        // Delete the file from storage if it's not a URL
        if ($media->disk !== 'url') {
            Storage::disk($media->disk)->delete($media->path);
        }

        $media->delete();

        return response()->json(['message' => 'Media deleted successfully']);
    }
}
