<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    /**
     * List all posts.
     */
    public function index()
    {
        $posts = Post::with(['user', 'comments'])->latest()->get();
        return response()->json($posts, 200);
    }

    /**
     * Create a new post (for Doctor, Lawyer, or Association).
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|string',
            'category' => 'nullable|string|max:100',
        ]);

        $post = Post::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'content' => $request->content,
            'image' => $request->image,
            'category' => $request->category,
        ]);

        return response()->json([
            'message' => 'Post successfully created.',
            'data' => $post
        ], 201);
    }

    /**
     * Display a specific post with comments.
     */
    public function show($id)
    {
        $post = Post::with(['user', 'comments.user'])->findOrFail($id);
        return response()->json($post, 200);
    }
    // GET /api/posts/home
public function home()
{
    $posts = Post::with(['user'])
        ->latestForHome()
        ->get();

    return response()->json($posts);
}


    /**
     * Delete a post (owner or admin).
     */
    public function destroy($id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.'], 200);
    }
}
