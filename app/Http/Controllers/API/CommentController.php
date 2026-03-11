<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * List all comments (admin or public).
     */
    // public function index()
    // {
    //     $comments = Comment::with(['user', 'commentable'])->latest()->get();
    //     return response()->json($comments, 200);
    // }

            public function index()
        {
            $comments = Comment::with(['user', 'commentable'])->latest()->get();
            return response()->json($comments, 200);
        }

    /**
     * Store a new comment for a Post, Product, or Service.
     */
    public function store(Request $request)
    {
        $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'user_id' => Auth::id(),
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $request->commentable_id,
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Comment successfully added.',
            'data' => $comment
        ], 201);
    }

    /**
     * Show a single comment.
     */
    public function show($id)
    {
        $comment = Comment::with(['user'])->findOrFail($id);
        return response()->json($comment, 200);
    }

    /**
     * Delete comment (only owner or admin).
     */
    public function destroy($id)
    {
        $comment = Comment::findOrFail($id);

        if ($comment->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully.'], 200);
    }
}
