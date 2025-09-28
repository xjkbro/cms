<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('posts/posts', [
            'posts' => Post::with('category','user')->get(),
            'categories' => Category::where('user_id', $request->user()->id)->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $categories = \App\Models\Category::where('user_id', $request->user()->id)->get();
        return Inertia::render('posts/edit', [
            'post' => null,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'excerpt' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'is_draft' => 'nullable|boolean',
        ]);

        $data['user_id'] = $request->user()->id;

    $post = \App\Models\Post::create($data);

        return redirect()->route('posts');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Post $post)
    {
        $categories = \App\Models\Category::where('user_id', $request->user()->id)->get();
        return Inertia::render('posts/edit', [
            'post' => $post,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'excerpt' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'is_draft' => 'nullable|boolean',
        ]);

        $post->update($data);

        return redirect()->route('posts');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $post->delete();
        return redirect()->route('posts');
    }

    /**
     * Handle image uploads from the editor.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120', // max 5MB
        ]);
        $user = $request->user();
        $file = $request->file('file');
        $timestamp = now()->timestamp;
        $filename = $timestamp . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());

        try {
            // store on the public disk (storage/app/public/{userId}/filename)
            $storedPath = Storage::disk('public')->putFileAs((string) $user->id, $file, $filename);

            // generate the public url using the public storage symlink (public/storage)
            // use asset() to avoid static analysis issues with the Filesystem contract
            $url = asset('storage/' . ltrim($storedPath, '/'));

            // Create media record
            $media = \App\Models\Media::create([
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

            return response()->json(['url' => $url, 'media' => $media]);
        } catch (\Exception $e) {
            logger()->error('Image upload failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Upload failed'], 500);
        }
    }
}
